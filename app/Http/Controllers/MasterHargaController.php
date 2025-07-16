<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MasterHarga;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MasterHargaPivotExport;
use App\Rules\UniqueMasterHargaTipe;

class MasterHargaController extends Controller
{
    public function index(Request $request)
    {
        $selectedGrade = $request->input('grade');
        $filterStartDate = $request->input('start_date');
        $filterEndDate = $request->input('end_date');

        $tanggalHeaders = collect();
        $startDate = $filterStartDate ? Carbon::parse($filterStartDate) : Carbon::create(2025, 5, 20);
        $endDate = $filterEndDate ? Carbon::parse($filterEndDate) : Carbon::now();

        if ($endDate->gte($startDate)) {
            $period = CarbonPeriod::create($startDate, '1 day', $endDate);
            foreach ($period as $date) {
                if ($date->day == 5 || $date->day == 20) {
                    $tanggalHeaders->push($date->copy());
                }
            }
        }
        
        $query = MasterHarga::query();

        if ($selectedGrade) {
            $query->where('grade', $selectedGrade);
        }
        
        if ($tanggalHeaders->isNotEmpty()) {
            $query->whereBetween('tanggal', [$tanggalHeaders->first()->format('Y-m-d'), $tanggalHeaders->last()->format('Y-m-d')]);
        } else {
            $query->whereRaw('1 = 0');
        }

        $semuaHarga = $query->get();

        $dataPivot = $semuaHarga
            ->groupBy(fn($item) => $item->tipe . '|' . $item->grade)
            ->map(function($group) {
                $hargaPerTanggal = $group->keyBy(fn($item) => $item->tanggal->format('Y-m-d'))
                                        ->map(fn($item) => $item->harga);
                return (object)[
                    'tipe'  => $group->first()->tipe,
                    'grade' => $group->first()->grade,
                    'harga_per_tanggal' => $hargaPerTanggal,
                ];
            })
            ->sortBy(['tipe', 'grade'])->values();

        $grades = MasterHarga::select('grade')->distinct()->orderBy('grade')->pluck('grade');

        return view('pages.master-harga.index', [
            'dataPivot' => $dataPivot,
            'tanggalHeaders' => $tanggalHeaders,
            'grades' => $grades,
            'selectedGrade' => $selectedGrade,
            'filterStartDate' => $filterStartDate,
            'filterEndDate' => $filterEndDate,
        ]);
    }

    public function store(Request $request)
    {
        // 1. Validasi input dari form
        $request->validate([
            'tipe' => [
                'required', 
                'string', 
                'max:255', 
                new UniqueMasterHargaTipe($request->input('grade')) 
            ],
            'grade' => 'required|string|max:255',
            'harga' => 'required|numeric|min:0',
            'tanggal' => 'required|date',
        ]);

        // 2. Proses data harga
        $harga = $request->input('harga');

        // 3. Gunakan updateOrCreate untuk mencegah duplikat dan memungkinkan edit
        MasterHarga::updateOrCreate(
            [
                // Kondisi pencarian: jika ada data dengan 3 kombinasi ini, maka akan di-update
                'tipe'    => $request->input('tipe'),
                'grade'   => $request->input('grade'),
                'tanggal' => $request->input('tanggal'),
            ],
            [
                // Nilai yang akan di-update atau dibuat
                'harga'   => $harga,
            ]
        );

        // 4. Redirect kembali ke halaman index dengan pesan sukses
        return redirect()->route('master-harga.index')->with('success', 'Data harga berhasil disimpan!');
    }

    public function export()
    {
        // Buat nama file dengan tanggal hari ini
        $fileName = 'master_harga_pivot_' . now()->format('Y-m-d') . '.xlsx';

        // Panggil class export utama dan download filenya
        return Excel::download(new MasterHargaPivotExport(), $fileName);
    }

    public function importPivot(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pasted_data' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Data yang ditempelkan tidak boleh kosong.']);
        }

        $pastedData = trim($request->input('pasted_data'));
        $rows = explode("\n", $pastedData);

        $headerRow = array_shift($rows);
        $headers = explode("\t", $headerRow);
        $dateColumns = [];

        for ($i = 2; $i < count($headers); $i++) {
            try {
                $date = Carbon::parse(trim($headers[$i]));
                $dateColumns[$i] = $date->format('Y-m-d');
            } catch (\Exception $e) {
                continue;
            }
        }

        if (empty($dateColumns)) {
            return response()->json(['success' => false, 'message' => 'Tidak ada kolom tanggal yang valid pada header.']);
        }

        $updatedCount = 0;
        $createdCount = 0;

        foreach ($rows as $row) {
            if (empty(trim($row))) continue;
            $columns = explode("\t", $row);
            $tipe = trim($columns[0] ?? '');
            $grade = trim($columns[1] ?? '');

            if (empty($tipe) || empty($grade)) continue;

            foreach ($dateColumns as $columnIndex => $dbDate) {
                $hargaStr = trim($columns[$columnIndex] ?? '');

                if (!empty($hargaStr) && is_numeric(str_replace('.', '', $hargaStr))) {
                    $harga = (float)str_replace('.', '', $hargaStr);

                    // Cari data yang cocok, atau buat instance baru jika tidak ada
                    $masterHarga = MasterHarga::firstOrNew([
                        'tipe'    => $tipe,
                        'grade'   => $grade,
                        'tanggal' => $dbDate,
                    ]);

                    // Cek apakah data tersebut sudah ada di database sebelumnya
                    if ($masterHarga->exists) {
                        // DATA SUDAH ADA -> Lakukan logic UPDATE
                        if ($masterHarga->harga != $harga) {
                            $masterHarga->harga = $harga;
                            $updatedCount++; // Tambah hitungan update
                        }
                    } else {
                        // DATA BELUM ADA -> Lakukan logic CREATE
                        $masterHarga->harga = $harga;
                        $createdCount++; // Tambah hitungan create
                    }

                    // Simpan perubahan (baik update maupun create)
                    $masterHarga->save();
                }
            }
        }

        // Pesan laporan yang baru dan lebih informatif
        $message = "Proses impor selesai. Data baru dibuat: {$createdCount}, Data diperbarui: {$updatedCount}.";
        return response()->json(['success' => true, 'message' => $message]);
    }

    public function updateCell(Request $request)
    {
        // 1. Validasi diubah: 'harga' sekarang boleh kosong (nullable)
        $validator = Validator::make($request->all(), [
            'tipe'      => 'required|string',
            'grade'     => 'required|string',
            'tanggal'   => 'required|date_format:Y-m-d',
            'harga'     => 'nullable|numeric|min:0', // Diubah dari 'required' menjadi 'nullable'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $harga = $request->input('harga');
        $kondisi = [
            'tipe'    => $request->input('tipe'),
            'grade'   => $request->input('grade'),
            'tanggal' => $request->input('tanggal'),
        ];

        // 2. Logika baru: Cek apakah input harga kosong atau tidak
        if (is_null($harga) || $harga === '') {
            // JIKA HARGA KOSONG, HAPUS DATA
            MasterHarga::where($kondisi)->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Harga berhasil dihapus!',
                'formatted_harga' => '-' // Kirim kembali strip untuk ditampilkan
            ]);

        } else {
            // JIKA HARGA ADA, BUAT ATAU UPDATE DATA (seperti sebelumnya)
            $data = MasterHarga::updateOrCreate($kondisi, ['harga' => $harga]);

            return response()->json([
                'success' => true,
                'message' => 'Harga berhasil diperbarui!',
                'formatted_harga' => number_format($data->harga, 0, ',', '.')
            ]);
        }
    }

    public function updateRow(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'original_tipe'  => 'required|string',
            'original_grade' => 'required|string',
            'new_tipe'       => 'required|string|max:255',
            'new_grade'      => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $originalTipe = $request->input('original_tipe');
        $originalGrade = $request->input('original_grade');
        $newTipe = $request->input('new_tipe');
        $newGrade = $request->input('new_grade');

        $normOldTipe = MasterHarga::normalizeString($originalTipe);
        $normOldGrade = MasterHarga::normalizeString($originalGrade);
        $normNewTipe = MasterHarga::normalizeString($newTipe);
        $normNewGrade = MasterHarga::normalizeString($newGrade);

        if ($normOldTipe === $normNewTipe && $normOldGrade === $normNewGrade) {
            return response()->json(['success' => true, 'message' => 'Tidak ada perubahan.']);
        }

        $conflictingTipes = MasterHarga::where('tipe_normalisasi', $normNewTipe)->get();
        if ($conflictingTipes->isNotEmpty()) {
            foreach ($conflictingTipes as $record) {
                $normalizedDbGrade = MasterHarga::normalizeString($record->grade);
                if ($normalizedDbGrade === $normNewGrade) {
                    return response()->json(['success' => false, 'message' => 'Kombinasi Tipe dan Grade tersebut sudah ada.'], 422);
                }
            }
        }
        
        // Ambil semua record yang cocok dengan KODE YANG SUDAH DIPERBAIKI
        $recordsToUpdate = MasterHarga::where('tipe', $originalTipe)
                                    ->where('grade', $originalGrade) // <-- PERBAIKAN DI SINI
                                    ->get();

        // Loop dan simpan satu per satu untuk memicu model event 'saving'
        foreach ($recordsToUpdate as $record) {
            $record->tipe = $newTipe;
            $record->grade = $newGrade;
            $record->save(); // Ini akan memastikan 'tipe_normalisasi' juga ter-update
        }

        return response()->json(['success' => true, 'message' => 'Data baris berhasil diperbarui. Halaman akan dimuat ulang.']);
    }
}
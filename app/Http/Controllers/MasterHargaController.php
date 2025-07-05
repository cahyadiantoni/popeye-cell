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

class MasterHargaController extends Controller
{
    public function index(Request $request) 
    {
        // 1. Ambil input filter dari request
        $selectedGrade = $request->input('grade');
        $filterStartDate = $request->input('start_date');
        $filterEndDate = $request->input('end_date');

        // 2. Generate header tanggal dinamis berdasarkan filter atau default
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
        
        // 3. Ambil data harga dengan query yang sudah difilter
        $query = MasterHarga::query();

        if ($selectedGrade) {
            $query->where('grade', $selectedGrade);
        }
        // Filter data berdasarkan rentang tanggal yang digunakan untuk header
        // Ini memastikan data yang diambil relevan dengan kolom yang ditampilkan
        if ($tanggalHeaders->isNotEmpty()) {
            $query->whereBetween('tanggal', [$tanggalHeaders->first()->format('Y-m-d'), $tanggalHeaders->last()->format('Y-m-d')]);
        } else {
            // Jika tidak ada header tanggal (rentang tidak valid), jangan ambil data sama sekali
            $query->whereRaw('1 = 0');
        }

        $semuaHarga = $query->get();

        // 4. Olah data menjadi format pivot (logika ini tidak berubah)
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

        // 5. Ambil daftar grade unik untuk dropdown filter
        $grades = MasterHarga::select('grade')->distinct()->orderBy('grade')->pluck('grade');

        // 6. Kirim semua data yang dibutuhkan ke view
        return view('pages.master-harga.index', [
            'dataPivot' => $dataPivot,
            'tanggalHeaders' => $tanggalHeaders,
            'grades' => $grades,
            'selectedGrade' => $selectedGrade,
            'filterStartDate' => $filterStartDate,
            'filterEndDate' => $filterEndDate,
        ]);
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
                    $harga = (float)str_replace('.', '', $hargaStr) * 1000;

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
}
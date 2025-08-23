<?php

namespace App\Http\Controllers;

use App\Models\Gudang;
use App\Models\Inventaris;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use App\Exports\InventarisExport;

class InventarisController extends Controller
{
    /**
     * Menampilkan daftar resource dengan filter.
     */
    public function index(Request $request)
    {
        // Ambil hanya Gudang yang ada di data inventaris untuk opsi filter
        $usedGudangIds = Inventaris::whereNotNull('gudang_id')->distinct()->pluck('gudang_id');
        $filterGudangs = Gudang::whereIn('id', $usedGudangIds)->get();

        // Ambil hanya Kode Toko yang unik dari data inventaris untuk opsi filter
        $filterKodeTokos = Inventaris::whereNotNull('kode_toko')->distinct()->orderBy('kode_toko')->pluck('kode_toko');
        
        // Mulai query dasar
        $query = Inventaris::with('gudang')->latest();

        // Terapkan filter Tanggal (tgl)
        if ($request->filled('start_date')) {
            $query->where('tgl', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('tgl', '<=', $request->end_date);
        }

        // Terapkan filter Gudang
        if ($request->filled('gudang_id')) {
            $query->where('gudang_id', $request->gudang_id);
        }

        // Terapkan filter Kode Toko
        if ($request->filled('kode_toko')) {
            $query->where('kode_toko', $request->kode_toko);
        }

        // Eksekusi query setelah semua filter diterapkan
        $inventaris = $query->get();

        // Kirim semua data yang diperlukan ke view
        return view('pages.data-inventaris.index', [
            'inventaris' => $inventaris,
            'filterGudangs' => $filterGudangs,
            'filterKodeTokos' => $filterKodeTokos,
            'gudangs' => Gudang::all() // 'gudangs' tetap dikirim untuk modal Add/Edit
        ]);
    }

    public function store(Request $request)
    {
        // Validasi
        $validatedData = $request->validate([
            'gudang_id' => 'nullable|exists:t_gudang,id',
            'asal_barang' => 'nullable|string|max:255', // BARU
            'nama' => 'nullable|string|max:255',
            'kode_toko' => 'nullable|string|max:255',
            'nama_toko' => 'nullable|string|max:255',
            'lok_spk' => 'nullable|string|max:255|unique:inventaris,lok_spk',
            'jenis' => 'nullable|string', // DIUBAH
            'tipe' => 'nullable|string|max:255',
            'kelengkapan' => 'nullable|string',
            'keterangan' => 'nullable|string',
        ]);

        // DIUBAH: Otomatis ubah 'jenis' ke huruf besar jika ada
        if (isset($validatedData['jenis'])) {
            $validatedData['jenis'] = strtoupper($validatedData['jenis']);
        }

        if (isset($validatedData['kelengkapan'])) {
            $validatedData['kelengkapan'] = strtoupper($validatedData['kelengkapan']);
        }

        // Tambahkan status '1' dan tgl saat ini secara otomatis
        $validatedData['status'] = 1;
        $validatedData['tgl'] = now();

        Inventaris::create($validatedData);

        return redirect()->route('data-inventaris.index')->with('success', 'Data inventaris berhasil ditambahkan!');
    }

    public function update(Request $request, $id)
    {
        $inventaris = Inventaris::findOrFail($id);
        
        // Validasi
        $validatedData = $request->validate([
            'gudang_id' => 'nullable|exists:t_gudang,id',
            'asal_barang' => 'nullable|string|max:255', // BARU
            'nama' => 'nullable|string|max:255',
            'kode_toko' => 'nullable|string|max:255',
            'nama_toko' => 'nullable|string|max:255',
            'lok_spk' => ['nullable', 'string', 'max:255', Rule::unique('inventaris', 'lok_spk')->ignore($inventaris->id)],
            'jenis' => 'nullable|string', // DIUBAH
            'tipe' => 'nullable|string|max:255',
            'kelengkapan' => 'nullable|string',
            'keterangan' => 'nullable|string',
        ]);
        
        // DIUBAH: Otomatis ubah 'jenis' ke huruf besar jika ada
        if (isset($validatedData['jenis'])) {
            $validatedData['jenis'] = strtoupper($validatedData['jenis']);
        }

        if (isset($validatedData['kelengkapan'])) {
            $validatedData['kelengkapan'] = strtoupper($validatedData['kelengkapan']);
        }

        // Update data, 'tgl' tidak akan pernah diubah saat edit
        $inventaris->update($validatedData);
        return redirect()->route('data-inventaris.index')->with('success', 'Data inventaris berhasil diperbarui!');
    }

    public function gantian(Request $request, Inventaris $inventari)
    {
        $request->validate(['alasan_gantian' => 'required|string|max:1000']);
        try {
            $inventari->update([
                'status' => 2,
                'tgl_gantian' => now(),
                'alasan_gantian' => $request->alasan_gantian,
            ]);
            return response()->json(['status' => 'success', 'message' => 'Status barang berhasil diubah.']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan.'], 500);
        }
    }

    /**
     * Menangani upload batch data inventaris dari file Excel.
     */
    public function batchUpload(Request $request)
    {
        $request->validate(['file' => 'required|mimes:xlsx,xls']);

        try {
            DB::transaction(function () use ($request) {
                $rows = Excel::toArray([], $request->file('file'))[0];
                $errors = [];
                $dataToInsert = [];
                $lokSpksInFile = [];

                foreach ($rows as $index => $row) {
                    if ($index === 0) continue;

                    $lokSpk = $row[3] ?? null;

                    if (empty($lokSpk)) {
                        continue;
                    }
                    
                    if (in_array($lokSpk, $lokSpksInFile)) {
                        $errors[] = "Error di baris Excel #" . ($index + 1) . ": LOK SPK '$lokSpk' duplikat di dalam file ini.";
                        continue;
                    }
                    $lokSpksInFile[] = $lokSpk;

                    // DIUBAH: 'gudang_id' digantikan 'asal_barang' dari excel
                    // Kolom 7 sekarang untuk 'asal_barang'
                    $asalBarang = $row[7] ?? null;
                    
                    $rowData = [
                        'nama' => $row[0],
                        'kode_toko' => $row[1],
                        'nama_toko' => $row[2],
                        'lok_spk' => $lokSpk,
                        'jenis' => $row[4] ? strtoupper($row[4]) : null, // DIUBAH: langsung uppercase
                        'tipe' => $row[5],
                        'kelengkapan' => $row[6] ? strtoupper($row[6]) : null,
                        'asal_barang' => $asalBarang, // DIUBAH
                        'keterangan' => $row[8],
                    ];
                    
                    $validator = Validator::make($rowData, [
                        'nama' => 'required|max:255',
                        'kode_toko' => 'required|max:255',
                        'nama_toko' => 'required|max:255',
                        'lok_spk' => 'required|max:255|unique:inventaris,lok_spk',
                        'jenis' => 'required', // DIUBAH
                        'tipe' => 'required|max:255',
                        'kelengkapan' => 'required',
                        'asal_barang' => 'nullable|string|max:255', // DIUBAH
                        'keterangan' => 'nullable',
                    ]);

                    if ($validator->fails()) {
                        $errors[] = "Error di baris Excel #" . ($index + 1) . ": " . implode(', ', $validator->errors()->all());
                    } else {
                        $dataToInsert[] = [
                            'tgl' => now(),
                            'nama' => $rowData['nama'],
                            'kode_toko' => $rowData['kode_toko'],
                            'nama_toko' => $rowData['nama_toko'],
                            'lok_spk' => $rowData['lok_spk'],
                            'jenis' => $rowData['jenis'],
                            'tipe' => $rowData['tipe'],
                            'kelengkapan' => $rowData['kelengkapan'],
                            'asal_barang' => $rowData['asal_barang'], // DIUBAH
                            'gudang_id' => null, // DIUBAH: gudang_id dibuat null saat upload excel
                            'keterangan' => $rowData['keterangan'],
                            'status' => 1,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }

                if (!empty($errors)) {
                    throw new \Exception(implode('<br>', $errors));
                }
                if (!empty($dataToInsert)) {
                    Inventaris::insert($dataToInsert);
                }
            });
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Upload Gagal: <br>' . $e->getMessage());
        }

        return redirect()->route('data-inventaris.index')->with('success', 'Data inventaris berhasil di-upload secara batch!');
    }

    /**
     * Menangani permintaan export data ke Excel.
     */
    public function exportExcel(Request $request)
    {
        // Ambil semua parameter filter dari request
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $gudangId = $request->query('gudang_id');
        $kodeToko = $request->query('kode_toko');

        // Buat nama file dinamis dengan tanggal
        $fileName = 'data-inventaris-' . now()->format('d-m-Y') . '.xlsx';
        
        // Panggil Export Class dengan parameter filter dan download file
        return Excel::download(new InventarisExport($startDate, $endDate, $gudangId, $kodeToko), $fileName);
    }
}
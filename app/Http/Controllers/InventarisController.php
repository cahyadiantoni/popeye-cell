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
        // ==========================================================
        // BAGIAN 1: Mengambil Opsi untuk Dropdown Filter
        // ==========================================================

        // Ambil hanya Gudang yang ada di data inventaris untuk opsi filter
        $usedGudangIds = Inventaris::whereNotNull('gudang_id')->distinct()->pluck('gudang_id');
        $filterGudangs = Gudang::whereIn('id', $usedGudangIds)->get();

        // Ambil hanya Kode Toko yang unik dari data inventaris untuk opsi filter
        $filterKodeTokos = Inventaris::whereNotNull('kode_toko')->distinct()->orderBy('kode_toko')->pluck('kode_toko');

        // ==========================================================
        // BAGIAN 2: Membangun Query Utama dengan Kondisi Filter
        // ==========================================================
        
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
        // Validasi tanpa 'tgl'
        $validatedData = $request->validate([
            'gudang_id' => 'nullable|exists:t_gudang,id',
            'nama' => 'nullable|string|max:255',
            'kode_toko' => 'nullable|string|max:255',
            'nama_toko' => 'nullable|string|max:255',
            'lok_spk' => 'nullable|string|max:255|unique:inventaris,lok_spk',
            'jenis' => 'nullable|string|in:TAB,HP,LP,LAIN LAIN',
            'tipe' => 'nullable|string|max:255',
            'kelengkapan' => 'nullable|string|in:BOX,BTG',
            'keterangan' => 'nullable|string',
        ]);

        // Tambahkan status '1' dan tgl saat ini secara otomatis
        $validatedData['status'] = 1;
        $validatedData['tgl'] = now(); // DIUBAH

        Inventaris::create($validatedData);

        return redirect()->route('data-inventaris.index')->with('success', 'Data inventaris berhasil ditambahkan!');
    }

    public function update(Request $request, $id)
    {
        $inventaris = Inventaris::findOrFail($id);
        
        // Validasi tanpa 'tgl'
        $validatedData = $request->validate([
            'gudang_id' => 'nullable|exists:t_gudang,id',
            'nama' => 'nullable|string|max:255',
            'kode_toko' => 'nullable|string|max:255',
            'nama_toko' => 'nullable|string|max:255',
            'lok_spk' => ['nullable', 'string', 'max:255', Rule::unique('inventaris', 'lok_spk')->ignore($inventaris->id)],
            'jenis' => 'nullable|string|in:TAB,HP,LP,LAIN LAIN',
            'tipe' => 'nullable|string|max:255',
            'kelengkapan' => 'nullable|string|in:BOX,BTG',
            'keterangan' => 'nullable|string',
        ]);
        
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

                    // DIUBAH: Pengecekan utama sekarang ada di sini.
                    // Ambil LOK SPK dari kolom yang sesuai (indeks 3)
                    $lokSpk = $row[3] ?? null;

                    // Jika LOK SPK kosong, anggap baris ini tidak valid dan abaikan tanpa error.
                    // Ini juga akan menangani baris kosong di akhir file Excel.
                    if (empty($lokSpk)) {
                        continue;
                    }
                    
                    // Cek duplikasi LOK SPK di dalam file Excel itu sendiri
                    if (in_array($lokSpk, $lokSpksInFile)) {
                        $errors[] = "Error di baris Excel #" . ($index + 1) . ": LOK SPK '$lokSpk' duplikat di dalam file ini.";
                        continue;
                    }
                    $lokSpksInFile[] = $lokSpk;

                    // Jika gudang_id kosong di Excel, gunakan default '7'
                    $gudangId = !empty($row[7]) ? $row[7] : 7;
                    
                    $rowData = [
                        'nama' => $row[0],
                        'kode_toko' => $row[1],
                        'nama_toko' => $row[2],
                        'lok_spk' => $lokSpk,
                        'jenis' => $row[4],
                        'tipe' => $row[5],
                        'kelengkapan' => $row[6],
                        'gudang_id' => $gudangId,
                        'keterangan' => $row[8],
                    ];
                    
                    // Validator tetap 'required' untuk memastikan baris yang relevan terisi lengkap
                    $validator = Validator::make($rowData, [
                        'nama' => 'required|max:255',
                        'kode_toko' => 'required|max:255',
                        'nama_toko' => 'required|max:255',
                        'lok_spk' => 'required|max:255|unique:inventaris,lok_spk',
                        'jenis' => 'required|in:TAB,HP,LP,LAIN LAIN',
                        'tipe' => 'required|max:255',
                        'kelengkapan' => 'required|in:BOX,BTG',
                        'gudang_id' => 'required|integer|exists:t_gudang,id',
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
                            'gudang_id' => $rowData['gudang_id'],
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
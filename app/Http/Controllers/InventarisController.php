<?php

namespace App\Http\Controllers;

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
        // Opsi filter Kode Toko
        $filterKodeTokos = Inventaris::whereNotNull('kode_toko')
            ->distinct()
            ->orderBy('kode_toko')
            ->pluck('kode_toko');

        // Query dasar
        $query = Inventaris::latest();

        // Filter tanggal
        if ($request->filled('start_date')) {
            $query->where('tgl', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('tgl', '<=', $request->end_date);
        }

        // Filter kode_toko
        if ($request->filled('kode_toko')) {
            $query->where('kode_toko', $request->kode_toko);
        }

        // Filter asal_barang (opsional)
        if ($request->filled('asal_barang')) {
            $query->where('asal_barang', 'like', '%'.$request->asal_barang.'%');
        }

        $inventaris = $query->get();

        return view('pages.data-inventaris.index', [
            'inventaris'      => $inventaris,
            'filterKodeTokos' => $filterKodeTokos,
        ]);
    }

    /**
     * Simpan data baru.
     * Syarat: minimal ada 1 field terisi (jangan semua kosong).
     */
    public function store(Request $request)
    {
        // Server-side guard: jika semua field kosong, tolak.
        $fields = [
            'asal_barang', 'nama', 'kode_toko', 'nama_toko',
            'lok_spk', 'jenis', 'tipe', 'kelengkapan', 'keterangan'
        ];
        $allEmpty = true;
        foreach ($fields as $f) {
            $val = $request->input($f);
            if (is_string($val)) $val = trim($val);
            if (!empty($val)) { $allEmpty = false; break; }
        }
        if ($allEmpty) {
            return back()
                ->withInput()
                ->withErrors(['form' => 'Form tidak boleh kosong semua. Isi minimal satu kolom.']);
        }

        // Validasi (semua nullable; lok_spk boleh kosong)
        $validatedData = $request->validate([
            'asal_barang' => 'nullable|string|max:255',
            'nama'        => 'nullable|string|max:255',
            'kode_toko'   => 'nullable|string|max:255',
            'nama_toko'   => 'nullable|string|max:255',
            'lok_spk'     => 'nullable|string|max:255|unique:inventaris,lok_spk',
            'jenis'       => 'nullable|string',
            'tipe'        => 'nullable|string|max:255',
            'kelengkapan' => 'nullable|string',
            'keterangan'  => 'nullable|string',
        ]);

        // Normalisasi kapital
        if (isset($validatedData['jenis'])) {
            $validatedData['jenis'] = strtoupper($validatedData['jenis']);
        }
        if (isset($validatedData['kelengkapan'])) {
            $validatedData['kelengkapan'] = strtoupper($validatedData['kelengkapan']);
        }

        // Set default
        $validatedData['status'] = 1;
        $validatedData['tgl']    = now();

        Inventaris::create($validatedData);

        return redirect()->route('data-inventaris.index')->with('success', 'Data inventaris berhasil ditambahkan!');
    }

    /**
     * Update data.
     * (Tidak memaksa minimal 1 field, tapi bisa ditambah jika ingin.)
     */
    public function update(Request $request, $id)
    {
        $inventaris = Inventaris::findOrFail($id);

        $validatedData = $request->validate([
            'asal_barang' => 'nullable|string|max:255',
            'nama'        => 'nullable|string|max:255',
            'kode_toko'   => 'nullable|string|max:255',
            'nama_toko'   => 'nullable|string|max:255',
            'lok_spk'     => ['nullable', 'string', 'max:255', Rule::unique('inventaris', 'lok_spk')->ignore($inventaris->id)],
            'jenis'       => 'nullable|string',
            'tipe'        => 'nullable|string|max:255',
            'kelengkapan' => 'nullable|string',
            'keterangan'  => 'nullable|string',
        ]);

        if (isset($validatedData['jenis'])) {
            $validatedData['jenis'] = strtoupper($validatedData['jenis']);
        }
        if (isset($validatedData['kelengkapan'])) {
            $validatedData['kelengkapan'] = strtoupper($validatedData['kelengkapan']);
        }

        $inventaris->update($validatedData);

        return redirect()->route('data-inventaris.index')->with('success', 'Data inventaris berhasil diperbarui!');
    }

    public function gantian(Request $request, Inventaris $inventari)
    {
        $request->validate(['alasan_gantian' => 'required|string|max:1000']);
        try {
            $inventari->update([
                'status'         => 2,
                'tgl_gantian'    => now(),
                'alasan_gantian' => $request->alasan_gantian,
            ]);
            return response()->json(['status' => 'success', 'message' => 'Status barang berhasil diubah.']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan.'], 500);
        }
    }

    /**
     * Batch upload dari Excel:
     * - lok_spk boleh kosong (tetap insert)
     * - skip baris yang benar2 kosong
     * - cek duplikat lok_spk di file hanya jika lok_spk terisi
     */
    public function batchUpload(Request $request)
    {
        $request->validate(['file' => 'required|mimes:xlsx,xls']);

        try {
            DB::transaction(function () use ($request) {
                $rows = Excel::toArray([], $request->file('file'))[0] ?? [];
                $errors = [];
                $dataToInsert = [];
                $lokSpksInFile = [];

                foreach ($rows as $index => $row) {
                    if ($index === 0) continue; // skip header

                    // Map kolom (samakan dengan template)
                    // 0: nama, 1: kode_toko, 2: nama_toko, 3: lok_spk, 4: jenis, 5: tipe,
                    // 6: kelengkapan, 7: asal_barang, 8: keterangan
                    $nama        = $row[0] ?? null;
                    $kodeToko    = $row[1] ?? null;
                    $namaToko    = $row[2] ?? null;
                    $lokSpk      = $row[3] ?? null;
                    $jenis       = $row[4] ?? null;
                    $tipe        = $row[5] ?? null;
                    $kelengkapan = $row[6] ?? null;
                    $asalBarang  = $row[7] ?? null;
                    $keterangan  = $row[8] ?? null;

                    // Trim semua agar deteksi kosong akurat
                    $vals = [$nama, $kodeToko, $namaToko, $lokSpk, $jenis, $tipe, $kelengkapan, $asalBarang, $keterangan];
                    $vals = array_map(function($v){ return is_string($v) ? trim($v) : $v; }, $vals);

                    // Guard baris kosong total
                    $allEmpty = true;
                    foreach ($vals as $v) {
                        if (!empty($v)) { $allEmpty = false; break; }
                    }
                    if ($allEmpty) {
                        continue; // skip tanpa error
                    }

                    // Cek duplikat dalam file hanya jika lok_spk terisi
                    if (!empty($lokSpk)) {
                        if (in_array($lokSpk, $lokSpksInFile)) {
                            $errors[] = "Error di baris Excel #" . ($index + 1) . ": LOK SPK '$lokSpk' duplikat di dalam file ini.";
                            continue;
                        }
                        $lokSpksInFile[] = $lokSpk;
                    }

                    $rowData = [
                        'nama'        => $nama,
                        'kode_toko'   => $kodeToko,
                        'nama_toko'   => $namaToko,
                        'lok_spk'     => $lokSpk,
                        'jenis'       => $jenis ? strtoupper($jenis) : null,
                        'tipe'        => $tipe,
                        'kelengkapan' => $kelengkapan ? strtoupper($kelengkapan) : null,
                        'asal_barang' => $asalBarang,
                        'keterangan'  => $keterangan,
                    ];

                    // Validasi baris (lok_spk nullable)
                    $validator = Validator::make($rowData, [
                        'nama'        => 'nullable|max:255',
                        'kode_toko'   => 'nullable|max:255',
                        'nama_toko'   => 'nullable|max:255',
                        'lok_spk'     => 'nullable|max:255|unique:inventaris,lok_spk',
                        'jenis'       => 'nullable',
                        'tipe'        => 'nullable|max:255',
                        'kelengkapan' => 'nullable',
                        'asal_barang' => 'nullable|string|max:255',
                        'keterangan'  => 'nullable',
                    ]);

                    if ($validator->fails()) {
                        $errors[] = "Error di baris Excel #" . ($index + 1) . ": " . implode(', ', $validator->errors()->all());
                    } else {
                        $dataToInsert[] = [
                            'tgl'         => now(),
                            'nama'        => $rowData['nama'],
                            'kode_toko'   => $rowData['kode_toko'],
                            'nama_toko'   => $rowData['nama_toko'],
                            'lok_spk'     => $rowData['lok_spk'],
                            'jenis'       => $rowData['jenis'],
                            'tipe'        => $rowData['tipe'],
                            'kelengkapan' => $rowData['kelengkapan'],
                            'asal_barang' => $rowData['asal_barang'],
                            'keterangan'  => $rowData['keterangan'],
                            'status'      => 1,
                            'created_at'  => now(),
                            'updated_at'  => now(),
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
     * Export Excel (tanpa gudang_id).
     */
    public function exportExcel(Request $request)
    {
        $startDate  = $request->query('start_date');
        $endDate    = $request->query('end_date');
        $kodeToko   = $request->query('kode_toko');
        $asalBarang = $request->query('asal_barang');

        $fileName = 'data-inventaris-' . now()->format('d-m-Y') . '.xlsx';

        return Excel::download(new InventarisExport($startDate, $endDate, $kodeToko, $asalBarang), $fileName);
    }
}

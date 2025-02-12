<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\Gudang;
use App\Models\CekSO;
use App\Models\CekSOBarang;
use App\Models\CekSOFinished;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class CekSOController extends Controller
{
    public function index()
    {
        // Ambil semua gudang
        $allgudangs = Gudang::all();

        // Ambil semua data CekSO, urutkan berdasarkan updated_at terbaru
        $cekSOs = CekSO::orderBy('updated_at', 'desc')->get();

        // Proses data untuk yang belum selesai (is_finished == 0)
        foreach ($cekSOs as $cekSO) {
            if ($cekSO->is_finished == 0) {
                // Hitung jumlah_scan dari banyaknya row pada CekSOBarang berdasarkan t_cek_so_id
                $cekSO->jumlah_scan = CekSOBarang::where('t_cek_so_id', $cekSO->id)->count();

                // Hitung jumlah_stok dari banyaknya row pada Barang berdasarkan gudang_id yang sesuai
                $cekSO->jumlah_stok = Barang::where('gudang_id', $cekSO->gudang_id)->where('status_barang', 1)->count();

                // Set waktu_selesai menjadi "-"
                $cekSO->waktu_selesai = "-";
            }

            // Masukkan nama_gudang ke dalam cekSO
            $cekSO->nama_gudang = $cekSO->gudang->nama_gudang ?? '-';
        }

        return view('pages.cek-so.index', compact('allgudangs', 'cekSOs'));
    }

    public function store(Request $request)
    {
        // Validasi input
        $request->validate([
            'kode' => 'required|string|unique:t_cek_so,kode',
            'petugas' => 'required|string',
            'waktu_mulai' => 'required|date',
            'penerima_gudang_id' => 'required|exists:t_gudang,id'
        ]);

        try {
            // Simpan data ke database
            CekSO::create([
                'kode' => $request->kode,
                'petugas' => $request->petugas,
                'gudang_id' => $request->penerima_gudang_id,
                'jumlah_scan' => 0,
                'jumlah_stok' => 0,
                'waktu_mulai' => Carbon::parse($request->waktu_mulai),
                'waktu_selesai' => null,
                'hasil' => 0,
                'catatan' => '',
                'is_finished' => 0
            ]);

            return redirect()->back()->with('success', 'Cek SO berhasil dibuat!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function getLastKode($gudang_id)
    {
        // Ambil bulan & tahun saat ini
        $bulan = Carbon::now()->format('m'); // 01-12
        $tahun = Carbon::now()->format('y'); // 2 digit tahun (2025 -> 25)

        // Cari kode terakhir berdasarkan gudang_id, bulan, dan tahun
        $lastCekSO = CekSO::where('gudang_id', $gudang_id)
            ->whereRaw("DATE_FORMAT(waktu_mulai, '%m') = ?", [$bulan])
            ->whereRaw("DATE_FORMAT(waktu_mulai, '%y') = ?", [$tahun])
            ->orderBy('kode', 'desc')
            ->first();

        // Jika belum ada, mulai dari 001
        $nextNumber = $lastCekSO ? ((int) substr($lastCekSO->kode, -3) + 1) : 1;

        return response()->json(['next_number' => $nextNumber]);
    }

    public function show($id)
    {
        $cekso = CekSO::findOrFail($id);
    
        // Jika is_finished == 0, hitung jumlah_scan dan jumlah_stok
        if ($cekso->is_finished == 0) {
            $cekso->jumlah_scan = CekSOBarang::where('t_cek_so_id', $cekso->id)->count();
            $cekso->jumlah_stok = Barang::where('gudang_id', $cekso->gudang_id)->where('status_barang',1)->count();
            $cekso->waktu_selesai = "-";
        }
    
        // Ambil nama gudang
        $cekso->nama_gudang = $cekso->gudang->nama_gudang ?? '-';
    
        // Ambil daftar barang yang tidak ada di database
        $ceksoBarangnas = CekSOBarang::where('t_cek_so_id', $id)
            ->whereNotIn('lok_spk', function ($query) use ($cekso) {
                $query->select('lok_spk')
                    ->from('t_barang')
                    ->where('gudang_id', $cekso->gudang_id)
                    ->where('status_barang', 1);
            })
            ->get();

        // Hitung durasi jika waktu_selesai tidak kosong atau "-"
        if ($cekso->waktu_selesai == "-") {
            $cekso->durasi = "-";
        } else {
            $mulai = Carbon::parse($cekso->waktu_mulai);
            $selesai = Carbon::parse($cekso->waktu_selesai);
            $cekso->durasi = $mulai->diff($selesai)->format('%H:%I:%S');
            $cekso->waktu_selesai = Carbon::parse($cekso->waktu_selesai)->format('H:i (d M y)');
        }
        
        $cekso->waktu_mulai = Carbon::parse($cekso->waktu_mulai)->format('H:i (d M y)');

    
        return view('pages.cek-so.detail', compact('cekso', 'ceksoBarangnas'));
    }   

    public function showFinish($id)
    {
        $cekso = CekSO::findOrFail($id);

        $mulai = Carbon::parse($cekso->waktu_mulai);
        $selesai = Carbon::parse($cekso->waktu_selesai);
        $cekso->durasi = $mulai->diff($selesai)->format('%H:%I:%S');
        $cekso->waktu_selesai = Carbon::parse($cekso->waktu_selesai)->format('H:i (d M y)');
        $cekso->waktu_mulai = Carbon::parse($cekso->waktu_mulai)->format('H:i (d M y)');
        $cekso->nama_gudang = $cekso->gudang->nama_gudang ?? '-';

    
        return view('pages.cek-so.finished', compact('cekso'));
    }    

    public function getCekSOBarangs(Request $request, $id)
    {
        $cekso = CekSO::findOrFail($id);

        // Ambil semua barang di gudang tertentu
        $query = Barang::where('gudang_id', $cekso->gudang_id)
            ->where('status_barang', 1)
            ->leftJoin('t_cek_so_barang', function ($join) use ($id) {
                $join->on('t_cek_so_barang.lok_spk', '=', 't_barang.lok_spk')
                    ->where('t_cek_so_barang.t_cek_so_id', '=', $id);
            })
            ->select(
                't_barang.*',
                't_cek_so_barang.updated_at as scan_time', // Ambil waktu terakhir scan
                \DB::raw('IF(t_cek_so_barang.lok_spk IS NOT NULL, 1, 0) as is_scanned')
            );

        // **Filter berdasarkan status scan**
        if ($request->has('scan_status')) {
            if ($request->scan_status === "1") {
                $query->whereNotNull('t_cek_so_barang.lok_spk'); // Barang yang sudah discan
            } elseif ($request->scan_status === "0") {
                $query->whereNull('t_cek_so_barang.lok_spk'); // Barang yang belum discan
            }
        }

        // **Urutkan berdasarkan scan terbaru**
        $query->orderByDesc('scan_time'); // Menampilkan yang baru discan di atas

        return DataTables::of($query)
            ->rawColumns(['is_scanned'])
            ->make(true);
    }

    public function getCekSOFinish(Request $request, $id)
    {
        $cekso = CekSO::findOrFail($id);
    
        // Ambil data dari CekSOFinished dan gabungkan dengan data dari t_barang
        $query = CekSOFinished::where('t_cek_so_finished.t_cek_so_id', $id)
            ->leftJoin('t_barang', function ($join) use ($cekso) {
                $join->on('t_barang.lok_spk', '=', 't_cek_so_finished.lok_spk')
                    ->where('t_barang.gudang_id', '=', $cekso->gudang_id)
                    ->where('t_barang.status_barang', '=', 1);
            })
            ->select(
                't_cek_so_finished.lok_spk',
                't_cek_so_finished.status',
                't_cek_so_finished.updated_at as scan_time',
                't_barang.jenis',
                't_barang.tipe',
                't_barang.kelengkapan'
            );
    
        // Filter status hanya jika tidak kosong/null
        if (!is_null($request->scan_status) && $request->scan_status !== '') {
            $query->where('t_cek_so_finished.status', $request->scan_status);
        }
    
        // Urutkan berdasarkan scan terbaru
        $query->orderByDesc('scan_time');
    
        return DataTables::of($query)
            ->addColumn('jenis', function ($row) {
                return $row->jenis ?? '-';
            })
            ->addColumn('tipe', function ($row) {
                return $row->tipe ?? '-';
            })
            ->addColumn('kelengkapan', function ($row) {
                return $row->kelengkapan ?? '-';
            })
            ->addColumn('status', function ($row) {
                switch ($row->status) {
                    case 0:
                        return '<span class="badge bg-warning text-dark">Belum Discan</span>';
                    case 1:
                        return '<span class="badge bg-success">Sudah Discan</span>';
                    case 2:
                        return '<span class="badge bg-danger">Tidak Ada di Database</span>';
                    default:
                        return '<span class="badge bg-secondary">Tidak Diketahui</span>';
                }
            })
            ->rawColumns(['status'])
            ->make(true);
    }    

    public function scan(Request $request)
    {
        $request->validate([
            't_cek_so_id' => 'required|integer',
            'lok_spk' => 'required|string'
        ]);
    
        // Cek apakah barang sudah pernah discan
        $existing = CekSOBarang::where('t_cek_so_id', $request->t_cek_so_id)
                                ->where('lok_spk', $request->lok_spk)
                                ->exists();
    
        if ($existing) {
            return response()->json(['status' => 'duplicate']);
        }
    
        try {
            // Simpan data scan baru
            CekSOBarang::create([
                't_cek_so_id' => $request->t_cek_so_id,
                'lok_spk' => $request->lok_spk
            ]);
    
            // Ambil data CekSO
            $cekso = CekSO::findOrFail($request->t_cek_so_id);
    
            // Hitung jumlah scan dan jumlah stok
            $jumlahScan = CekSOBarang::where('t_cek_so_id', $cekso->id)->count();
            $jumlahStok = Barang::where('gudang_id', $cekso->gudang_id)->where('status_barang', 1)->count();
    
            // Cek barang yang tidak ada di database
            $ceksoBarangnas = CekSOBarang::where('t_cek_so_id', $cekso->id)
                ->whereNotIn('lok_spk', function ($query) use ($cekso) {
                    $query->select('lok_spk')
                          ->from('t_barang')
                          ->where('gudang_id', $cekso->gudang_id)
                          ->where('status_barang', 1);
                })
                ->exists(); // Gunakan exists() untuk efisiensi
    
            // Tentukan hasil berdasarkan aturan
            if ($jumlahScan != $jumlahStok) {
                $hasil = 0;
            } elseif ($jumlahScan == $jumlahStok && !$ceksoBarangnas) {
                $hasil = 1;
            } elseif ($jumlahScan == $jumlahStok && $ceksoBarangnas) {
                $hasil = 2;
            }
    
            // Update nilai di model CekSO
            $cekso->update([
                'jumlah_scan' => $jumlahScan,
                'jumlah_stok' => $jumlahStok,
                'hasil' => $hasil
            ]);
    
            return response()->json(['status' => 'success']);
    
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }   
    
    public function finish(Request $request)
    {
        $request->validate([
            't_cek_so_id' => 'required|integer',
            'catatan' => 'nullable|string'
        ]);
    
        try {
            $cekso = CekSO::findOrFail($request->t_cek_so_id);
    
            // Ambil semua lok_spk yang sudah ada di CekSOFinished agar tidak ada duplikasi
            $existingLokSpk = CekSOFinished::where('t_cek_so_id', $cekso->id)
                ->pluck('lok_spk')
                ->map(fn($lok_spk) => strtolower($lok_spk)) // Ubah ke huruf kecil
                ->toArray();
    
            // Ambil semua lok_spk yang sudah discan
            $scannedItems = CekSOBarang::where('t_cek_so_id', $cekso->id)
                ->pluck('lok_spk')
                ->map(fn($lok_spk) => strtolower($lok_spk)) // Ubah ke huruf kecil
                ->toArray();
    
            // Ambil semua lok_spk dari tabel t_barang berdasarkan gudang_id dan status_barang = 1
            $warehouseItems = Barang::where('gudang_id', $cekso->gudang_id)
                ->where('status_barang', 1)
                ->pluck('lok_spk')
                ->map(fn($lok_spk) => strtolower($lok_spk)) // Ubah ke huruf kecil
                ->toArray();
    
            $dataToInsert = [];
    
            // Gabungkan semua barang yang bisa muncul, lalu ubah ke huruf kecil
            $allLokSpk = array_unique(array_merge($scannedItems, $warehouseItems));
    
            foreach ($allLokSpk as $lok_spk) {
                // Lewati jika lok_spk sudah ada di CekSOFinished
                if (in_array(strtolower($lok_spk), $existingLokSpk)) {
                    continue;
                }
    
                // Tentukan status berdasarkan aturan:
                if (in_array($lok_spk, $warehouseItems) && !in_array($lok_spk, $scannedItems)) {
                    $status = 0; // Barang ada di warehouse tapi belum discan
                } elseif (in_array($lok_spk, $scannedItems) && in_array($lok_spk, $warehouseItems)) {
                    $status = 1; // Barang ada di warehouse dan sudah discan
                } elseif (in_array($lok_spk, $scannedItems) && !in_array($lok_spk, $warehouseItems)) {
                    $status = 2; // Barang sudah discan tapi tidak ada di warehouse
                }
    
                $dataToInsert[] = [
                    't_cek_so_id' => $cekso->id,
                    'lok_spk' => $lok_spk, // Simpan dalam bentuk asli
                    'status' => $status,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }
    
            // Insert semua data ke CekSOFinished jika ada data baru
            if (!empty($dataToInsert)) {
                CekSOFinished::insert($dataToInsert);
            }
    
            // Hitung jumlah scan dan jumlah stok
            $jumlahScan = count($scannedItems);
            $jumlahStok = count($warehouseItems);
    
            // Cek barang yang tidak ada di database
            $ceksoBarangnas = CekSOBarang::where('t_cek_so_id', $cekso->id)
                ->whereNotIn('lok_spk', function ($query) use ($cekso) {
                    $query->select('lok_spk')
                        ->from('t_barang')
                        ->where('gudang_id', $cekso->gudang_id)
                        ->where('status_barang', 1);
                })
                ->exists(); // Gunakan exists() untuk efisiensi
    
            // Tentukan hasil berdasarkan aturan
            if ($jumlahScan !== $jumlahStok) {
                $hasil = 0;
            } elseif ($jumlahScan === $jumlahStok && !$ceksoBarangnas) {
                $hasil = 1;
            } elseif ($jumlahScan === $jumlahStok && $ceksoBarangnas) {
                $hasil = 2;
            }
    
            // Update tabel t_cek_so dengan jumlah_scan, jumlah_stok, hasil, is_finished, waktu_selesai, dan catatan
            $cekso->update([
                'jumlah_scan' => $jumlahScan,
                'jumlah_stok' => $jumlahStok,
                'hasil' => $hasil,
                'is_finished' => 1,
                'waktu_selesai' => now(),
                'catatan' => $request->catatan
            ]);
    
            return response()->json(['status' => 'success', 'message' => 'Data berhasil dikirim!']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    } 
    
    public function upload(Request $request)
    {
        $request->validate([
            'filedata' => 'required|mimes:xlsx,csv',
            't_cek_so_id' => 'required|integer'
        ]);
    
        $t_cek_so_id = $request->t_cek_so_id;
        $errors = [];
        $success = [];
        
        try {
            // Baca file Excel
            $file = $request->file('filedata');
            $data = Excel::toArray([], $file);
            
            $lokSpks = collect();
            foreach ($data[0] as $index => $row) {
                // Lewati baris pertama jika merupakan header
                if ($index === 0) continue;
                
                // Validasi kolom di Excel
                if (!isset($row[0]) || empty($row[0])) {
                    $errors[] = "Baris " . ($index + 1) . " memiliki sel kosong.";
                    continue;
                }
                
                $lokSpks->push($row[0]);
            }
            $lokSpks = $lokSpks->unique();
    
            // Cek apakah barang sudah pernah discan
            $existing = CekSOBarang::where('t_cek_so_id', $t_cek_so_id)
                                    ->whereIn('lok_spk', $lokSpks)
                                    ->exists();
            if ($existing) {
                $lokSpks = $lokSpks->diff(
                    CekSOBarang::where('t_cek_so_id', $t_cek_so_id)
                                ->whereIn('lok_spk', $lokSpks)
                                ->pluck('lok_spk')
                );
            }
    
            // Simpan data scan baru
            foreach ($lokSpks as $lok_spk) {
                CekSOBarang::create([
                    't_cek_so_id' => $t_cek_so_id,
                    'lok_spk' => $lok_spk
                ]);
            }
    
            // Ambil data CekSO
            $cekso = CekSO::findOrFail($t_cek_so_id);
            
            // Hitung jumlah scan dan jumlah stok
            $jumlahScan = CekSOBarang::where('t_cek_so_id', $cekso->id)->count();
            $jumlahStok = Barang::where('gudang_id', $cekso->gudang_id)->where('status_barang', 1)->count();
            
            // Cek barang yang tidak ada di database
            $ceksoBarangnas = CekSOBarang::where('t_cek_so_id', $cekso->id)
                ->whereNotIn('lok_spk', function ($query) use ($cekso) {
                    $query->select('lok_spk')
                          ->from('t_barang')
                          ->where('gudang_id', $cekso->gudang_id)
                          ->where('status_barang', 1);
                })
                ->exists();
    
            // Tentukan hasil berdasarkan aturan
            if ($jumlahScan != $jumlahStok) {
                $hasil = 0;
            } elseif ($jumlahScan == $jumlahStok && !$ceksoBarangnas) {
                $hasil = 1;
            } elseif ($jumlahScan == $jumlahStok && $ceksoBarangnas) {
                $hasil = 2;
            }
    
            // Update nilai di model CekSO
            $cekso->update([
                'jumlah_scan' => $jumlahScan,
                'jumlah_stok' => $jumlahStok,
                'hasil' => $hasil
            ]);
    
            $success[] = 'Data berhasil diproses.';
            return redirect()->back()->with('success', implode('<br>', $success))->with('errors', $errors);
    
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
    
}

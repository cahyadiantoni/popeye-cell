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
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use App\Exports\CekSoExport; 

class CekSOController extends Controller
{
    public function index()
    {
        $allgudangs = Gudang::all();
        $cekSOs = CekSO::orderBy('updated_at', 'desc')->get();

        foreach ($cekSOs as $cekSO) {
            if ($cekSO->is_finished == 0) {
                // DIUBAH: Panggil fungsi recalculate untuk memastikan data summary terbaru
                $this->recalculateCekSO($cekSO->id); 
                $cekSO->refresh(); // Ambil ulang data dari DB setelah di-recalculate
                $cekSO->waktu_selesai = "-";
            }
            $cekSO->nama_gudang = $cekSO->gudang->nama_gudang ?? '-';
        }

        return view('pages.cek-so.index', compact('allgudangs', 'cekSOs'));
    }

    public function store(Request $request)
    {
        $request->validate([ 'kode' => 'required|string|unique:t_cek_so,kode', 'petugas' => 'required|string', 'waktu_mulai' => 'required|date', 'penerima_gudang_id' => 'required|exists:t_gudang,id' ]);

        try {
            CekSO::create([
                'kode' => $request->kode,
                'petugas' => $request->petugas,
                'gudang_id' => $request->penerima_gudang_id,
                'jumlah_scan_sistem' => 0, // DIUBAH
                'jumlah_input_manual' => 0, // DIUBAH
                'jumlah_upload_excel' => 0, // DIUBAH
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
        $bulan = Carbon::now()->format('m');
        $tahun = Carbon::now()->format('y');
        $lastCekSO = CekSO::where('gudang_id', $gudang_id)->whereRaw("DATE_FORMAT(waktu_mulai, '%m') = ?", [$bulan])->whereRaw("DATE_FORMAT(waktu_mulai, '%y') = ?", [$tahun])->orderBy('kode', 'desc')->first();
        $nextNumber = $lastCekSO ? ((int) substr($lastCekSO->kode, -3) + 1) : 1;
        return response()->json(['next_number' => $nextNumber]);
    }
    
    public function show($id)
    {
        $cekso = CekSO::findOrFail($id);
        if ($cekso->is_finished) {
            // Jika sudah selesai, langsung tampilkan view pesan dan hentikan eksekusi
            return view('pages.cek-so.finished-message');
        }
        if ($cekso->is_finished == 0) {
            $this->recalculateCekSO($id);
            $cekso->refresh();
            $cekso->waktu_selesai = "-";
            $cekso->durasi = "-";
        } else {
            $mulai = Carbon::parse($cekso->waktu_mulai);
            $selesai = Carbon::parse($cekso->waktu_selesai);
            $cekso->durasi = $mulai->diff($selesai)->format('%H:%I:%S');
            $cekso->waktu_selesai = Carbon::parse($cekso->waktu_selesai)->format('H:i (d M y)');
        }
        
        $cekso->waktu_mulai = Carbon::parse($cekso->waktu_mulai)->format('H:i (d M y)');
        $cekso->nama_gudang = $cekso->gudang->nama_gudang ?? '-';
        $ceksoBarangnas = CekSOBarang::where('t_cek_so_id', $id)->whereNotIn('lok_spk', function ($query) use ($cekso) { $query->select('lok_spk')->from('t_barang')->where('gudang_id', $cekso->gudang_id)->where('status_barang', 1); })->get();
        
        $petugasScans = CekSOBarang::where('t_cek_so_id', $id)->whereNotNull('petugas_scan')->distinct()->pluck('petugas_scan');
        $lokasis = CekSOBarang::where('t_cek_so_id', $id)->whereNotNull('lokasi')->distinct()->pluck('lokasi');

        return view('pages.cek-so.detail', compact('cekso', 'ceksoBarangnas', 'petugasScans', 'lokasis'));
    }
    
    public function showGuest($id)
    {
        // Logikanya sama persis dengan method show()
        $cekso = CekSO::findOrFail($id);
        if ($cekso->is_finished) {
            // Jika sudah selesai, langsung tampilkan view pesan dan hentikan eksekusi
            return view('pages.cek-so.finished-message');
        }
        if ($cekso->is_finished == 0) {
            $this->recalculateCekSO($id);
            $cekso->refresh();
            $cekso->waktu_selesai = "-";
            $cekso->durasi = "-";
        } else {
            $mulai = Carbon::parse($cekso->waktu_mulai);
            $selesai = Carbon::parse($cekso->waktu_selesai);
            $cekso->durasi = $mulai->diff($selesai)->format('%H:%I:%S');
            $cekso->waktu_selesai = Carbon::parse($cekso->waktu_selesai)->format('H:i (d M y)');
        }
        
        $cekso->waktu_mulai = Carbon::parse($cekso->waktu_mulai)->format('H:i (d M y)');
        $cekso->nama_gudang = $cekso->gudang->nama_gudang ?? '-';
        
        $petugasScans = CekSOBarang::where('t_cek_so_id', $id)->whereNotNull('petugas_scan')->distinct()->pluck('petugas_scan');
        $lokasis = CekSOBarang::where('t_cek_so_id', $id)->whereNotNull('lokasi')->distinct()->pluck('lokasi');

        // Perbedaannya hanya di sini: me-return view yang berbeda
        return view('pages.cek-so.detail-guest', compact('cekso', 'petugasScans', 'lokasis'));
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
        
        $petugasScans = CekSOFinished::where('t_cek_so_id', $id)->whereNotNull('petugas_scan')->distinct()->pluck('petugas_scan');
        $lokasis = CekSOFinished::where('t_cek_so_id', $id)->whereNotNull('lokasi')->distinct()->pluck('lokasi');

        return view('pages.cek-so.finished', compact('cekso', 'petugasScans', 'lokasis'));
    }

    public function getCekSOBarangs(Request $request, $id)
    {
        $cekso = CekSO::findOrFail($id);
        $query = Barang::where('t_barang.gudang_id', $cekso->gudang_id)
            ->where('t_barang.status_barang', 1)
            ->leftJoin('t_cek_so_barang', function ($join) use ($id) {
                $join->on('t_cek_so_barang.lok_spk', '=', 't_barang.lok_spk')
                     ->where('t_cek_so_barang.t_cek_so_id', '=', $id);
            })
            ->select(
                // Alias tetap penting untuk kejelasan
                't_barang.lok_spk', 
                't_barang.jenis',
                't_barang.tipe',
                't_barang.kelengkapan',
                't_cek_so_barang.updated_at as scan_time',
                't_cek_so_barang.petugas_scan',
                't_cek_so_barang.lokasi',
                't_cek_so_barang.status as scan_status_val'
            );
        
        // Filter status dropdown tidak berubah
        if ($request->filled('scan_status')) {
            if ($request->scan_status == 'ditemukan') {
                $query->whereIn('t_cek_so_barang.status', [1, 3, 4]);
            } else if ($request->scan_status == 'belum_ditemukan') {
                $query->whereNull('t_cek_so_barang.status');
            } else {
                $query->where('t_cek_so_barang.status', $request->scan_status);
            }
        }
        
        // Filter petugas dan lokasi tidak berubah
        if ($request->filled('petugas_scan')) { $query->where('t_cek_so_barang.petugas_scan', $request->petugas_scan); }
        if ($request->filled('lokasi')) { $query->where('t_cek_so_barang.lokasi', $request->lokasi); }
        
        // Pengurutan tidak berubah
        $query->orderByDesc('scan_time');
    
        return DataTables::of($query)
            // BARU: Mengambil alih logika pencarian global
            ->filter(function ($query) use ($request) {
                if ($request->has('search') && !is_null($request->input('search.value'))) {
                    $keyword = $request->input('search.value');
                    // Buat grup WHERE ( ... OR ... OR ... )
                    $query->where(function($q) use ($keyword) {
                        $q->where('t_barang.lok_spk', 'like', "%{$keyword}%")
                          ->orWhere('t_barang.jenis', 'like', "%{$keyword}%")
                          ->orWhere('t_barang.tipe', 'like', "%{$keyword}%")
                          ->orWhere('t_barang.kelengkapan', 'like', "%{$keyword}%");
                    });
                }
            })
            ->make(true);
    }
    
    public function getCekSOFinish(Request $request, $id)
    {
        $cekso = CekSO::findOrFail($id);
        $query = CekSOFinished::where('t_cek_so_finished.t_cek_so_id', $id)
            ->leftJoin('t_barang', function ($join) use ($cekso) { $join->on('t_barang.lok_spk', '=', 't_cek_so_finished.lok_spk')->where('t_barang.gudang_id', '=', $cekso->gudang_id)->where('t_barang.status_barang', '=', 1); })
            ->select('t_cek_so_finished.*', 't_barang.jenis', 't_barang.tipe', 't_barang.kelengkapan');
        
        if ($request->filled('scan_status')) { $query->where('t_cek_so_finished.status', $request->scan_status); }
        if ($request->filled('petugas_scan')) { $query->where('t_cek_so_finished.petugas_scan', $request->petugas_scan); }
        if ($request->filled('lokasi')) { $query->where('t_cek_so_finished.lokasi', $request->lokasi); }
        
        $query->orderByDesc('t_cek_so_finished.updated_at');
    
        return DataTables::of($query)
            ->addColumn('jenis', fn($row) => $row->jenis ?? '-')
            ->addColumn('tipe', fn($row) => $row->tipe ?? '-')
            ->addColumn('kelengkapan', fn($row) => $row->kelengkapan ?? '-')
            // DIUBAH: Logika status badge
            ->addColumn('status_badge', function ($row) {
                switch ($row->status) {
                    case 0: return '<span class="badge bg-warning text-dark">Belum Discan</span>';
                    case 1: return '<span class="badge bg-success">Scan Sistem</span>';
                    case 2: return '<span class="badge bg-danger">Tidak Ada di DB</span>';
                    case 3: return '<span class="badge bg-info">Input Manual</span>';
                    case 4: return '<span class="badge bg-primary">Upload Excel</span>';
                    default: return '<span class="badge bg-secondary">Tidak Diketahui</span>';
                }
            })
            ->rawColumns(['status_badge'])
            ->make(true);
    }

    public function scan(Request $request)
    {
        $request->validate([
            't_cek_so_id' => 'required|integer',
            'lok_spk' => 'required|string',
            'petugas_scan' => 'required|string',
            'lokasi' => 'nullable|string'
        ]);
        
        $cekso = CekSO::findOrFail($request->t_cek_so_id);

        if (CekSOBarang::where('t_cek_so_id', $request->t_cek_so_id)->where('lok_spk', $request->lok_spk)->exists()) {
            return response()->json(['status' => 'duplicate']);
        }

        try {
            CekSOBarang::create([
                't_cek_so_id' => $request->t_cek_so_id,
                'lok_spk' => $request->lok_spk,
                'status' => 1,
                'petugas_scan' => $request->petugas_scan,
                'lokasi' => $request->lokasi
            ]);
            
            $foundInMaster = Barang::where('gudang_id', $cekso->gudang_id)
                                ->where('lok_spk', $request->lok_spk)
                                ->where('status_barang', 1)
                                ->exists();
            
            $message = $foundInMaster 
                ? "Barang '{$request->lok_spk}' berhasil discan dan DITEMUKAN di database."
                : "Barang '{$request->lok_spk}' berhasil dicatat tapi TIDAK ADA di master database.";

            $this->recalculateCekSO($request->t_cek_so_id);

            // -- TAMBAHAN BARU --
            // Hitung jumlah barang di lokasi yang sama untuk SO ini
            $locationCount = CekSOBarang::where('t_cek_so_id', $request->t_cek_so_id)
                                        ->where('lokasi', $request->lokasi)
                                        ->count();
            // ------
            
            return response()->json([
                'status' => 'success',
                'found_in_master' => $foundInMaster,
                'message' => $message,
                'location_count' => $locationCount // Kirim jumlah ke frontend
            ]);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
    
    public function finish(Request $request)
    {
        // ... (Logika finish tidak perlu banyak berubah, hanya di bagian update summary)
        try {
            $cekso = CekSO::findOrFail($request->t_cek_so_id);
    
            $scannedItemsCollection = CekSOBarang::where('t_cek_so_id', $cekso->id)->get();
            $scannedItemsMap = $scannedItemsCollection->keyBy(fn($item) => strtolower($item->lok_spk));
    
            $warehouseItemsCollection = Barang::where('gudang_id', $cekso->gudang_id)->where('status_barang', 1)->get();
            $warehouseItemsMap = $warehouseItemsCollection->keyBy(fn($item) => strtolower($item->lok_spk));
    
            $allLokSpk_lower_unique = array_unique(array_merge($scannedItemsMap->keys()->all(), $warehouseItemsMap->keys()->all()));
            
            $dataToInsert = [];
            foreach ($allLokSpk_lower_unique as $lok_spk_lower) {
                $scannedItem = $scannedItemsMap->get($lok_spk_lower);
                $warehouseItem = $warehouseItemsMap->get($lok_spk_lower);
                
                $status = null; $originalLokSpk = $scannedItem->lok_spk ?? $warehouseItem->lok_spk;
                $petugas_scan = $scannedItem->petugas_scan ?? null;
                $lokasi = $scannedItem->lokasi ?? null;

                if ($scannedItem) { // Ditemukan
                    $status = $scannedItem->status; // Ambil status asli (1, 3, atau 4)
                    if (!$warehouseItem) $status = 2; // Ditemukan tapi tidak ada di master DB
                } else { // Tidak ditemukan
                    $status = 0;
                }
    
                $dataToInsert[] = ['t_cek_so_id' => $cekso->id, 'lok_spk' => $originalLokSpk, 'status' => $status, 'petugas_scan' => $petugas_scan, 'lokasi' => $lokasi, 'created_at' => now(), 'updated_at' => now() ];
            }
            
            if (!empty($dataToInsert)) { CekSOFinished::insert($dataToInsert); }
            
            // Panggil recalculate untuk final update
            $summary = $this->recalculateCekSO($cekso->id);
            
            $cekso->update([
                'is_finished' => 1,
                'waktu_selesai' => now(),
                'catatan' => $request->catatan,
                'hasil' => $summary['hasil'] // Pastikan hasil terbaru tersimpan
            ]);
    
            return response()->json(['status' => 'success', 'message' => 'Stok opname berhasil diselesaikan!', 'redirect_url' => route('cekso.showFinish', $cekso->id) ]);
        } catch (\Exception $e) {
            \Log::error('Error finishing CekSO: ' . $e->getMessage(), ['cek_so_id' => $request->t_cek_so_id]);
            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan saat mengakhiri stok opname.']);
        }
    }
    
    public function upload(Request $request)
    {
        $request->validate(['filedata' => 'required|mimes:xlsx,csv', 't_cek_so_id' => 'required|integer', 'petugas_scan' => 'required|string', 'lokasi' => 'nullable|string' ]);
        try {
            $t_cek_so_id = $request->t_cek_so_id;
            $data = Excel::toArray([], $request->file('filedata'));
            $lokSpksToInsert = collect($data[0] ?? [])->skip(1)->map(fn($row) => trim($row[0]))->filter();

            if ($lokSpksToInsert->isEmpty()) return redirect()->back()->with('error', 'File Excel kosong atau format tidak sesuai.');
            
            $existingLokSpks = CekSOBarang::where('t_cek_so_id', $t_cek_so_id)->whereIn('lok_spk', $lokSpksToInsert)->pluck('lok_spk');
            $newLokSpks = $lokSpksToInsert->diff($existingLokSpks)->unique();
    
            $dataToInsert = $newLokSpks->map(fn($lok_spk) => [
                't_cek_so_id' => $t_cek_so_id, 'lok_spk' => $lok_spk,
                'status' => 4, // DIUBAH: Status untuk Excel Upload
                'petugas_scan' => $request->petugas_scan, 'lokasi' => $request->lokasi,
                'created_at' => now(), 'updated_at' => now(),
            ])->all();
            
            if (!empty($dataToInsert)) { CekSOBarang::insert($dataToInsert); }
            $this->recalculateCekSO($t_cek_so_id);
            return redirect()->back()->with('success', count($dataToInsert) . ' data baru berhasil di-upload.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan saat upload: ' . $e->getMessage());
        }
    }

    public function manualInput(Request $request)
    {
        $request->validate([
            't_cek_so_id' => 'required|integer',
            'lok_spk' => 'required|string',
            'petugas_scan' => 'required|string',
            'lokasi' => 'nullable|string'
        ]);
        
        try {
            $cekso = CekSO::findOrFail($request->t_cek_so_id);
            $lok_spk_raw = trim($request->lok_spk);

            if (CekSOBarang::where('t_cek_so_id', $request->t_cek_so_id)->whereRaw('LOWER(lok_spk) = ?', [mb_strtolower($lok_spk_raw)])->exists()) {
                return response()->json(['status' => 'duplicate', 'message' => 'LOK_SPK sudah pernah diinput.']);
            }

            $foundInMaster = Barang::where('gudang_id', $cekso->gudang_id)
                                ->where('lok_spk', $lok_spk_raw)
                                ->where('status_barang', 1)
                                ->exists();

            CekSOBarang::create([
                't_cek_so_id' => $request->t_cek_so_id, 
                'lok_spk' => $lok_spk_raw, 
                'status' => 3,
                'petugas_scan' => $request->petugas_scan, 
                'lokasi' => $request->lokasi, 
            ]);
            
            $message = $foundInMaster 
                ? "LOK_SPK '{$lok_spk_raw}' berhasil ditambahkan dan DITEMUKAN di database."
                : "LOK_SPK '{$lok_spk_raw}' ditambahkan, tapi TIDAK ADA di master database.";

            $summary = $this->recalculateCekSO($request->t_cek_so_id);

            // -- TAMBAHAN BARU --
            // Hitung jumlah barang di lokasi yang sama untuk SO ini
            $locationCount = CekSOBarang::where('t_cek_so_id', $request->t_cek_so_id)
                                        ->where('lokasi', $request->lokasi)
                                        ->count();
            // ------
            
            return response()->json([
                'status' => 'success', 
                'message' => $message,
                'found_in_master' => $foundInMaster,
                'summary' => $summary,
                'location_count' => $locationCount // Kirim jumlah ke frontend
            ]);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
    
    
    public function getCekSONotInMaster(Request $request, $id)
    {
        $cekso = CekSO::findOrFail($id);
    
        $query = CekSOBarang::where('t_cek_so_id', $id)
            ->whereNotIn('lok_spk', function($query) use ($cekso) {
                $query->select('lok_spk')
                      ->from('t_barang')
                      ->where('gudang_id', $cekso->gudang_id)
                      ->where('status_barang', 1);
            })
            ->select('t_cek_so_barang.*');
    
        if ($request->filled('petugas_scan')) { $query->where('petugas_scan', $request->petugas_scan); }
        if ($request->filled('lokasi')) { $query->where('lokasi', $request->lokasi); }
    
        // BARU: Tambahkan pengurutan berdasarkan input terbaru
        $query->orderByDesc('updated_at');
    
        return DataTables::of($query)
            ->addColumn('status_badge', function ($row) {
                 switch ($row->status) {
                    case 1: return '<span class="badge bg-success">Scan Sistem</span>';
                    case 3: return '<span class="badge bg-info">Input Manual</span>';
                    case 4: return '<span class="badge bg-primary">Upload Excel</span>';
                    default: return '<span class="badge bg-secondary">Tidak Diketahui</span>';
                }
            })
            ->rawColumns(['status_badge'])
            ->make(true);
    }

    // DIUBAH: Fungsi recalculate disesuaikan dengan kolom baru
    private function recalculateCekSO($cek_so_id) {
        $cekso = CekSO::findOrFail($cek_so_id);
    
        $counts = CekSOBarang::where('t_cek_so_id', $cekso->id)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $jumlahScanSistem = $counts->get(1, 0);
        $jumlahInputManual = $counts->get(3, 0);
        $jumlahUploadExcel = $counts->get(4, 0);
        $totalDitemukan = $jumlahScanSistem + $jumlahInputManual + $jumlahUploadExcel;
    
        $jumlahStok = Barang::where('gudang_id', $cekso->gudang_id)->where('status_barang', 1)->count();
    
        $ceksoBarangnas = CekSOBarang::where('t_cek_so_id', $cekso->id)->whereIn('status', [1,3,4])
            ->whereNotIn('lok_spk', function ($q) use ($cekso) { $q->select('lok_spk')->from('t_barang')->where('gudang_id', $cekso->gudang_id)->where('status_barang', 1); })->exists();
    
        $hasil = 0;
        if ($totalDitemukan == $jumlahStok && !$ceksoBarangnas) {
            $hasil = 1;
        } elseif ($totalDitemukan == $jumlahStok && $ceksoBarangnas) {
            $hasil = 2;
        }
    
        $cekso->update([
            'jumlah_scan_sistem' => $jumlahScanSistem,
            'jumlah_input_manual' => $jumlahInputManual,
            'jumlah_upload_excel' => $jumlahUploadExcel,
            'jumlah_stok' => $jumlahStok,
            'hasil' => $hasil,
        ]);
    
        return ['hasil' => $hasil]; // Kembalikan hasil untuk fungsi finish
    }
    
    public function exportExcel($id)
    {
        $cekso = CekSO::findOrFail($id);
        
        // Buat nama file yang deskriptif
        $fileName = 'CekSO_' . $cekso->kode . '_' . now()->format('Ymd') . '.xlsx';
        
        // Panggil class CekSoExport dan unduh filenya
        return Excel::download(new CekSoExport($cekso), $fileName);
    }
}
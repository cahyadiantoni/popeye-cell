<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\Gudang;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\ReturnModel;
use App\Models\ReturnBarang;
use App\Models\Kirim;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Models\TransaksiJual;
use App\Models\TransaksiJualBawah;
use App\Models\TransaksiJualOnline;
use App\Models\TransaksiJualOutlet;

class TransaksiReturnController extends Controller
{
    public function index(Request $request)
    {
        $query = ReturnModel::with(['returnBarang'])->orderBy('created_at', 'desc');
    
        $roleUser = optional(Auth::user())->role;
        $gudangId = optional(Auth::user())->gudang_id;

        // Terapkan logika filter yang sama
        if($roleUser == 'admin'){
            $daftarGudang = ['RTO-JK', 'RTO-AD', 'RTO-PY'];
        
            if ($request->filled('kode_faktur')) { // Menggunakan 'kode_faktur' agar konsisten dengan view lain
                $kodeFaktur = $request->kode_faktur;
        
                if (in_array($kodeFaktur, $daftarGudang)) {
                    // Filter berdasarkan nomor_return
                    $query->where('nomor_return', 'like', "$kodeFaktur-%");
                }
            }
        }else{
            switch ($gudangId) {
                case 8:
                    $query->where('nomor_return', 'like', "RTO-JK-%");
                    break;
                case 9:
                    $query->where('nomor_return', 'like', "RTO-AD-%");
                    break;
                case 10:
                    $query->where('nomor_return', 'like', "RTO-PY-%");
                    break;
                default:
                    // Batasi agar tidak bisa melihat data jika tidak punya gudang yang sesuai
                    $query->whereRaw('1 = 0');
                    break;
            }         
        }

        // Filter tambahan jika ada
        if ($request->filled('tanggal_mulai') && $request->filled('tanggal_selesai')) {
            $query->whereBetween('tgl_return', [$request->tanggal_mulai, $request->tanggal_selesai]);
        }
    
        $returns = $query->get()->map(function ($return) {
            $return->total_barang = $return->returnBarang->count();
            $return->total_harga = $return->returnBarang->sum('harga');
            return $return;
        });
    
        return view('pages.transaksi-return.index', compact('returns', 'roleUser'));
    }  

    public function create()
    {
        $roleUser = optional(Auth::user())->role;
        return view('pages.transaksi-return.create', compact('roleUser'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'filedata' => 'required|file|mimes:xlsx,xls',
            'tgl_return' => 'required|date',
            'nomor_return' => 'required|string|unique:t_return,nomor_return',
            'petugas' => 'required|string',
        ]);
    
        // --- PERUBAHAN 1: Tentukan Gudang ID Target berdasarkan Role ---
        $user = Auth::user();
        $targetGudangId = ($user->role == 'admin') ? 6 : $user->gudang_id;
        // ----------------------------------------------------------------

        $errors = [];
        $validLokSpk = [];
        $file = $request->file('filedata');
        $data = Excel::toArray([], $file);
    
        foreach ($data[0] as $index => $row) {
            if ($index === 0) continue;
    
            if (isset($row[0])) {
                $lokSpk = $row[0];
                $barang = Barang::where('lok_spk', $lokSpk)->first();
    
                if ($barang) {
                    if (in_array($barang->status_barang, [2])) {
                        $validLokSpk[] = [ 'lok_spk' => $lokSpk, 'harga' => $row[5], 'alasan' => $row[6], 'pedagang' => $row[7] ];
                    } else {
                        $errors[] = "Row " . ($index + 1) . ": Lok SPK '$lokSpk' memiliki status_barang yang tidak sesuai (bukan status 2).";
                    }
                } else {
                    if (is_string($row[0]) && is_string($row[1]) && is_string($row[2])) {
                        if (Barang::where('lok_spk', $row[0])->exists()) {
                            $errors[] = "Row " . ($index + 1) . " memiliki lok_spk duplikat di database: " . $row[0];
                            continue;
                        }
                        
                        // --- PERUBAHAN 2: Gunakan $targetGudangId saat membuat barang baru ---
                        $newBarang = Barang::create([
                            'lok_spk'       => $row[0],
                            'jenis'         => $row[1],
                            'tipe'          => $row[2],
                            'kelengkapan'   => $row[3],
                            'grade'         => $row[4],
                            'nama_petugas'  => $request->input('petugas'),
                            'dt_input'      => Carbon::now(),
                            'user_id'       => $user->id,
                            'gudang_id'     => $targetGudangId, // Diubah
                            'status_barang' => 1,
                        ]);

                        if($newBarang){
                            $validLokSpk[] = [ 'lok_spk' => $lokSpk, 'harga' => $row[5], 'alasan' => $row[6], 'pedagang' => $row[7] ];
                        }
                    } else {
                        $errors[] = "Row " . ($index + 1) . " Gagal tambah barang baru, data tidak lengkap.";
                    }
                }
            } else {
                $errors[] = "Row " . ($index + 1) . ": Data tidak valid (Lok SPK kosong).";
            }
        }
    
        if (!empty($validLokSpk)) {
            $return = ReturnModel::create([
                'nomor_return' => $request->input('nomor_return'),
                'tgl_return' => $request->input('tgl_return'),
                'user_id' => $user->id,    
                'keterangan' => $request->input('keterangan'), 
            ]);

            foreach ($validLokSpk as $item) {
                ReturnBarang::create([
                    'lok_spk' => $item['lok_spk'],
                    't_return_id' => $return->id, 
                    'harga' => $item['harga']*1000, 
                    'alasan' => $item['alasan'], 
                    'pedagang' => $item['pedagang'], 
                ]);

                // --- PERUBAHAN 3: Gunakan $targetGudangId saat update barang yang sudah ada ---
                Barang::where('lok_spk', $item['lok_spk'])->update([
                    'status_barang' => 1,
                    'gudang_id' => $targetGudangId, // Diubah
                ]);
            }
    
            return redirect()->route('transaksi-return.index')
                ->with('success', 'Return barang berhasil. ' . count($validLokSpk) . ' barang diproses.')
                ->with('errors', $errors);
        }
    
        return redirect()->route('transaksi-return.create')
            ->with('errors', $errors)
            ->withInput();
    }

    public function show($id)
    {
        // Menampilkan detail transaksi tertentu
        $return = ReturnModel::with(['user', 'returnBarang.barang'])->find($id);

        if (!$return) {
            // Jika transaksi tidak ditemukan, redirect dengan pesan error
            return redirect()->route('transaksi-return.index')->with('error', 'Transaksi tidak ditemukan.');
        }

        // Ambil semua ReturnBarang yang terkait dengan transaksi ini
        $returnBarangs = $return->returnBarang;

        // Hitung total barang
        $total_barang = $returnBarangs->count();

        // Hitung total harga
        $total_harga = $returnBarangs->sum('harga');

        // Tambahkan total_barang dan total_harga ke objek return
        $return->total_barang = $total_barang;
        $return->total_harga = $total_harga;

        return view('pages.transaksi-return.detail', compact('return', 'returnBarangs'));
    }

    public function destroy($id)
    {
        // Temukan transaksi return berdasarkan ID
        $return = ReturnModel::find($id);
    
        if (!$return) {
            // Jika transaksi tidak ditemukan, redirect dengan pesan error
            return redirect()->route('transaksi-return.index')->with('error', 'Transaksi tidak ditemukan.');
        }
    
        // Ambil semua ReturnBarang yang terkait dengan transaksi ini
        $returnBarangs = ReturnBarang::where('t_return_id', $id)->get();
    
        // Hapus semua ReturnBarang yang terkait dengan transaksi ini
        foreach ($returnBarangs as $returnBarang) {
            // Kembalikan status_barang dan gudang_id pada Barang
            Barang::where('lok_spk', $returnBarang->lok_spk)->update([
                'status_barang' => 2,
                'gudang_id' => 0,
            ]);
        }
    
        // Hapus semua ReturnBarang yang terkait
        ReturnBarang::where('t_return_id', $id)->delete();
    
        // Hapus transaksi return
        $return->delete();
    
        // Redirect ke halaman indeks dengan pesan sukses
        return redirect()->route('transaksi-return.index')->with('success', 'Transaksi berhasil dihapus dan status barang diperbarui.');
    }    

    public function getSuggest(Request $request)
    {
        $tglReturn = $request->tgl_return ? Carbon::parse($request->tgl_return) : Carbon::now();
        $currentMonthYear = $tglReturn->format('my');
        
        $gudangId = optional(Auth::user())->gudang_id;
        $kodeGudang = '';

        // Tentukan kode gudang berdasarkan gudang_id user
        switch ($gudangId) {
            case 8:  $kodeGudang = 'RTO-JK'; break;
            case 9:  $kodeGudang = 'RTO-AD'; break;
            case 10: $kodeGudang = 'RTO-PY'; break;
            default:
                // Jika tidak ada gudang yang cocok, kirim error
                return response()->json(['error' => 'User tidak memiliki gudang yang valid.'], 400);
        }

        $prefix = "$kodeGudang-$currentMonthYear-";

        $lastReturn = ReturnModel::where('nomor_return', 'like', "$prefix%")
            ->orderByRaw("CAST(SUBSTRING_INDEX(nomor_return, '-', -1) AS UNSIGNED) DESC")
            ->first();

        if ($lastReturn) {
            $lastNumber = (int) substr($lastReturn->nomor_return, strrpos($lastReturn->nomor_return, '-') + 1);
            $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '001';
        }

        $suggestedNoFak = $prefix . $newNumber;

        return response()->json(['suggested_no_fak' => $suggestedNoFak]);
    }

    public function addbarang(Request $request)
    {
        $request->validate([
            'filedata' => 'required|file|mimes:xlsx,xls',
            't_return_id' => 'required|exists:t_return,id',
        ]);
        
        // --- PERUBAHAN 4: Tentukan Gudang ID Target berdasarkan Role ---
        $user = Auth::user();
        $targetGudangId = ($user->role == 'admin') ? 6 : $user->gudang_id;
        // ----------------------------------------------------------------

        $errors = [];
        $validLokSpk = [];
        $processedLokSpk = [];
        $file = $request->file('filedata');
        $data = Excel::toArray([], $file);

        foreach ($data[0] as $index => $row) {
            if ($index === 0) continue;
            if (isset($row[0]) && isset($row[5]) && isset($row[6]) && isset($row[7])) {
                $lokSpk = $row[0];
                if (in_array($lokSpk, $processedLokSpk)) {
                    $errors[] = "Row " . ($index + 1) . ": Lok SPK '$lokSpk' duplikat di dalam file Excel.";
                    continue;
                }
                $processedLokSpk[] = $lokSpk;

                if (ReturnBarang::where('lok_spk', $lokSpk)->where('t_return_id', $request->input('t_return_id'))->exists()) {
                    $errors[] = "Row " . ($index + 1) . ": Lok SPK '$lokSpk' sudah ada di dalam return ini.";
                    continue;
                }

                $barang = Barang::where('lok_spk', $lokSpk)->first();
                if ($barang) {
                    if (in_array($barang->status_barang, [2])) {
                        $validLokSpk[] = ['lok_spk' => $lokSpk, 'harga' => $row[5], 'alasan' => $row[6], 'pedagang' => $row[7]];
                    } else {
                        $errors[] = "Row " . ($index + 1) . ": Lok SPK '$lokSpk' memiliki status_barang yang tidak sesuai.";
                    }
                } else {
                    if (is_string($row[0]) && is_string($row[1]) && is_string($row[2])) {
                        if (Barang::where('lok_spk', $row[0])->exists()) {
                            $errors[] = "Row " . ($index + 1) . " memiliki LOK SPK duplikat di database: " . $row[0];
                            continue;
                        }
                        
                        // --- PERUBAHAN 5: Gunakan $targetGudangId saat membuat barang baru ---
                        $newBarang = Barang::create([
                            'lok_spk' => $row[0], 'jenis' => $row[1], 'tipe' => $row[2], 'kelengkapan' => $row[3],
                            'grade' => $row[4], 'nama_petugas' => $user->name, 'dt_input' => Carbon::now(),
                            'user_id' => $user->id, 'gudang_id' => $targetGudangId, 'status_barang' => 1, // Diubah
                        ]);

                        if($newBarang){
                            $validLokSpk[] = ['lok_spk' => $lokSpk, 'harga' => $row[5], 'alasan' => $row[6], 'pedagang' => $row[7]];
                        }
                    } else {
                        $errors[] = "Row " . ($index + 1) . " Gagal menambah barang baru, data tidak lengkap.";
                    }
                }
            } else {
                $errors[] = "Row " . ($index + 1) . ": Data tidak valid (kurang kolom).";
            }
        }

        if (!empty($validLokSpk)) {
            foreach ($validLokSpk as $item) {
                // --- PERUBAHAN 6: Gunakan $targetGudangId saat update barang yang sudah ada ---
                Barang::where('lok_spk', $item['lok_spk'])->update([
                    'status_barang' => 1,
                    'gudang_id' => $targetGudangId, // Diubah
                ]);

                ReturnBarang::create([
                    'lok_spk' => $item['lok_spk'],
                    't_return_id' => $request->input('t_return_id'),
                    'harga' => $item['harga'] * 1000,
                    'alasan' => $item['alasan'],
                    'pedagang' => $item['pedagang'],
                ]);
            }
            return redirect()->back()
                ->with('success', count($validLokSpk) . ' barang berhasil ditambahkan.')
                ->with('errors', $errors);
        }

        return redirect()->back()->with('errors', $errors);
    }

    public function destroyBarang($id)
    {
        try {
            $transaksi = ReturnBarang::findOrFail($id);
            $lok_spk = $transaksi->lok_spk;

            // Cek apakah LOK SPK pernah digunakan di transaksi penjualan
            if ($this->isLokSpkInUse($lok_spk)) {
                Barang::where('lok_spk', $lok_spk)->update([
                    'status_barang' => 2, 
                    'gudang_id' => 0,     
                ]);
            } else {
                // JIKA BELUM PERNAH TERJUAL: Hapus permanen dari master barang
                Barang::where('lok_spk', $lok_spk)->delete();
            }

            $transaksi->delete();

            return redirect()->back()->with('success', 'Barang berhasil dihapus dari daftar return.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function updateBarang(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|exists:t_return_barang,id', 'lok_spk' => 'required|string',
            'harga' => 'required|numeric|min:0', 'alasan' => 'nullable|string',
            'pedagang' => 'nullable|string', 'jenis' => 'nullable|string', 'tipe' => 'nullable|string',
            'kelengkapan' => 'nullable|string', 'grade' => 'nullable|string',
        ]);

        try {
            DB::transaction(function () use ($validated, $request) {
                // --- PERUBAHAN 7: Tentukan Gudang ID Target berdasarkan Role ---
                $user = Auth::user();
                $targetGudangId = ($user->role == 'admin') ? 6 : $user->gudang_id;
                // ----------------------------------------------------------------

                $transaksi = ReturnBarang::findOrFail($validated['id']);
                $originalLokSpk = $transaksi->lok_spk;
                $newLokSpk = $validated['lok_spk'];

                if ($originalLokSpk === $newLokSpk) {
                    $transaksi->update($validated);
                    return;
                }

                if ($this->isLokSpkInUse($originalLokSpk)) {
                    Barang::where('lok_spk', $originalLokSpk)->update(['status_barang' => 2, 'gudang_id' => 0]);
                } else {
                    Barang::where('lok_spk', $originalLokSpk)->delete();
                }

                $barangBaru = Barang::where('lok_spk', $newLokSpk)->first();
                if ($barangBaru) {
                    if ($barangBaru->status_barang != 2) throw new \Exception("Barang '$newLokSpk' tidak berstatus 'terjual' (status 2).");
                    // --- PERUBAHAN 8: Gunakan $targetGudangId saat update barang yang ada ---
                    $barangBaru->update(['status_barang' => 1, 'gudang_id' => $targetGudangId]); // Diubah
                } else {
                    if (empty($validated['jenis']) || empty($validated['tipe'])) throw new \Exception("Jenis dan Tipe wajib diisi untuk barang baru.");
                    // --- PERUBAHAN 9: Gunakan $targetGudangId saat membuat barang baru ---
                    Barang::create([
                        'lok_spk' => $newLokSpk, 'jenis' => $validated['jenis'], 'tipe' => $validated['tipe'],
                        'kelengkapan' => $validated['kelengkapan'], 'grade' => $validated['grade'],
                        'nama_petugas' => $user->name, 'dt_input' => now(), 'user_id' => $user->id,
                        'gudang_id' => $targetGudangId, 'status_barang' => 1, // Diubah
                    ]);
                }

                $transaksi->update($validated);
            });
            return redirect()->back()->with('success', 'Barang return berhasil diupdate.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function riwayat()
    {
        // 1. Ambil semua data dari ReturnBarang
        $riwayat_barang = ReturnBarang::with(['returnModel', 'barang'])
                            // 2. Gabungkan dengan tabel t_return
                            ->join('t_return', 't_return_barang.t_return_id', '=', 't_return.id')
                            // 3. Urutkan berdasarkan kolom tgl_return dari tabel t_return (desc = terbaru dulu)
                            ->orderBy('t_return.tgl_return', 'desc')
                            // 4. Pilih semua kolom dari tabel asal untuk menghindari konflik
                            ->select('t_return_barang.*')
                            ->get();

        // 5. Kirim data ke view
        return view('pages.transaksi-return.riwayat', compact('riwayat_barang'));
    }

    private function isLokSpkInUse($lok_spk)
    {
        // Cek di masing-masing tabel transaksi
        $inJual = TransaksiJual::where('lok_spk', $lok_spk)->exists();
        $inJualBawah = TransaksiJualBawah::where('lok_spk', $lok_spk)->exists();
        $inJualOnline = TransaksiJualOnline::where('lok_spk', $lok_spk)->exists();
        $inJualOutlet = TransaksiJualOutlet::where('lok_spk', $lok_spk)->exists();

        // Kembalikan true jika ditemukan di salah satu tabel
        return $inJual || $inJualBawah || $inJualOnline || $inJualOutlet;
    }

    public function checkBarang($lok_spk)
    {
        // Cari barang berdasarkan lok_spk yang diketik
        $barang = Barang::find($lok_spk);

        if ($barang) {
            // Jika barang ditemukan, kirim datanya
            return response()->json([
                'exists' => true,
                'data' => $barang
            ]);
        } else {
            // Jika tidak ditemukan
            return response()->json(['exists' => false]);
        }
    }
}

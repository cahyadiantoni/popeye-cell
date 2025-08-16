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
    public function index()
    {
        // Mengambil semua transaksi return barang
        $returns = ReturnModel::with(['returnBarang'])->orderBy('created_at', 'desc')->get()->map(function ($return) {
            // Hitung total barang
            $total_barang = $return->returnBarang->count();
    
            // Hitung total harga
            $total_harga = $return->returnBarang->sum('harga');
    
            // Tambahkan total_barang dan total_harga ke objek return
            $return->total_barang = $total_barang;
            $return->total_harga = $total_harga;
    
            return $return;
        });
    
        return view('pages.transaksi-return.index', compact('returns'));
    }    

    public function create()
    {
        return view('pages.transaksi-return.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'filedata' => 'required|file|mimes:xlsx,xls',
        ]);
    
        // Inisialisasi variabel
        $errors = [];
        $validLokSpk = [];
    
        // Membaca file Excel
        $file = $request->file('filedata');
        $data = Excel::toArray([], $file);
    
        foreach ($data[0] as $index => $row) {
            // Lewati baris pertama jika merupakan header
            if ($index === 0) continue;
    
            // Validasi kolom di Excel
            if (isset($row[0])) {
                $lokSpk = $row[0]; // Lok SPK
    
                // Cari barang berdasarkan lok_spk
                $barang = Barang::where('lok_spk', $lokSpk)->first();
    
                if ($barang) {
                    // Cek apakah status_barang adalah 0 atau 1
                    if (in_array($barang->status_barang, [2])) {
    
                        // Simpan lok_spk untuk update nanti
                        $validLokSpk[] = [
                            'lok_spk' => $lokSpk,
                            'harga' => $row[5],
                            'alasan' => $row[6],
                            'pedagang' => $row[7],
                        ];
                    } else {
                        $errors[] = "Row " . ($index + 1) . ": Lok SPK '$lokSpk' memiliki status_barang yang tidak sesuai.";
                    }
                } else {
                    if (
                        is_string($row[0]) && // lok_spk
                        is_string($row[1]) && // jenis
                        is_string($row[2]) // tipe
                    ) {
                        // Cek apakah lok_spk sudah ada di database
                        if (Barang::where('lok_spk', $row[0])->exists()) {
                            // Tambahkan error jika lok_spk sudah ada di database
                            $errors[] = "Row " . ($index + 1) . " has a duplicate lok_spk in database: ";
                            continue; // Lewati penyimpanan untuk row ini
                        }
            
                        // Simpan data ke database jika valid
                        $newBarang = Barang::create([
                            'lok_spk' => $row[0],
                            'jenis' => $row[1],
                            'tipe' => $row[2],
                            'kelengkapan' => $row[3],
                            'grade' => $row[4],
                            'nama_petugas' => $request->input('petugas'),
                            'dt_input' => Carbon::now(),
                            'user_id' => Auth::id(),
                            'gudang_id' => 6,
                            'status_barang' => 1,
                        ]);

                        if($newBarang){
                            $validLokSpk[] = [
                                'lok_spk' => $lokSpk,
                                'harga' => $row[5],
                                'alasan' => $row[6],
                                'pedagang' => $row[7],
                            ];
                        }
                    } else {
                        // Tambahkan error jika tidak valid
                        $errors[] = "Row " . ($index + 1) . " Gagal tambah barang";
                    }
                }
            } else {
                $errors[] = "Row " . ($index + 1) . ": Data tidak valid (Lok SPK kosong).";
            }
        }
    
        // Simpan data Faktur jika ada data valid
        if (!empty($validLokSpk)) {
            
            $return = ReturnModel::create([
                'nomor_return' => $request->input('nomor_return'),
                'tgl_return' => $request->input('tgl_return'),  // Menyimpan tanggal return
                'user_id' => Auth::id(),    
                'keterangan' => $request->input('keterangan'), 
            ]);

            $returnId = $return->id;

            // Update Barang untuk lok_spk yang valid
            foreach ($validLokSpk as $item) {
                ReturnBarang::create([
                    'lok_spk' => $item['lok_spk'],
                    't_return_id' => $returnId, 
                    'harga' => $item['harga']*1000, 
                    'alasan' => $item['alasan'], 
                    'pedagang' => $item['pedagang'], 
                ]);

                Barang::where('lok_spk', $item['lok_spk'])->update([
                    'status_barang' => 1,
                    'gudang_id' => 6, 
                ]);
            }
    
            // Tampilkan pesan sukses dan error
            return redirect()->route('transaksi-return.index')
                ->with('success', 'Return barang berhasil. ' . count($validLokSpk) . ' barang diproses.')
                ->with('errors', $errors);
        }
    
        // Jika tidak ada data valid, hanya tampilkan error
        return redirect()->route('transaksi-return.index')
            ->with('errors', $errors);
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
        $currentMonthYear = $tglReturn->format('my'); // Menggunakan tanggal yang dipilih user

        // Ambil return terakhir dengan format yang sesuai
        $lastReturn = ReturnModel::where('nomor_return', 'like', "RTN-$currentMonthYear-%")
            ->orderByRaw("CAST(SUBSTRING(nomor_return, 10, LENGTH(nomor_return) - 9) AS UNSIGNED) DESC")
            ->first();

        // Tentukan nomor urut
        if ($lastReturn) {
            preg_match('/-(\d+)$/', $lastReturn->nomor_return, $matches);
            $lastNumber = isset($matches[1]) ? (int) $matches[1] : 0;
            $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '001';
        }

        // Format nomor return baru
        $suggestedNoFak = "RTN-$currentMonthYear-$newNumber";

        return response()->json(['suggested_no_fak' => $suggestedNoFak]);
    }

    public function addbarang(Request $request)
    {
        $request->validate([
            'filedata' => 'required|file|mimes:xlsx,xls',
            't_return_id' => 'required',
        ]);

        // Inisialisasi variabel
        $errors = [];
        $validLokSpk = [];
        $processedLokSpk = []; // Untuk memeriksa duplikat di file Excel

        // Membaca file Excel
        $file = $request->file('filedata');
        $data = Excel::toArray([], $file);

        foreach ($data[0] as $index => $row) {
            // Lewati baris pertama jika merupakan header
            if ($index === 0) continue;

            // Validasi kolom di Excel
            if (isset($row[0]) && isset($row[5]) && isset($row[6]) && isset($row[7])) {
                $lokSpk = $row[0]; // Lok SPK
                $harga = $row[5] * 1000;
                $alasan = $row[6];
                $pedagang = $row[7];

                // Cek duplikat lok_spk di dalam file Excel
                if (in_array($lokSpk, $processedLokSpk)) {
                    $errors[] = "Row " . ($index + 1) . ": Lok SPK '$lokSpk' duplikat di dalam file Excel.";
                    continue;
                }

                // Tambahkan lok_spk ke daftar yang sudah diproses
                $processedLokSpk[] = $lokSpk;

                // Cek duplikat kombinasi lok_spk dan t_return_id di database
                $existsInDatabase = ReturnBarang::where('lok_spk', $lokSpk)
                    ->where('t_return_id', $request->input('t_return_id'))
                    ->exists();

                if ($existsInDatabase) {
                    $errors[] = "Row " . ($index + 1) . ": Lok SPK '$lokSpk' dengan Nomor Faktur '{$request->input('t_return_id')}' sudah ada di database.";
                    continue;
                }

                // Cari barang berdasarkan lok_spk
                $barang = Barang::where('lok_spk', $lokSpk)->first();

                if ($barang) {
                    // Cek apakah status_barang adalah 0 atau 1
                    if (in_array($barang->status_barang, [2])) {
                        // Simpan lok_spk untuk update nanti
                        $validLokSpk[] = [
                            'lok_spk' => $lokSpk,
                            'harga' => $harga,
                            'alasan' => $alasan,
                            'pedagang' => $pedagang,
                        ];
                    } else {
                        $errors[] = "Row " . ($index + 1) . ": Lok SPK '$lokSpk' memiliki status_barang yang tidak sesuai.";
                    }
                } else {
                    if (
                        is_string($row[0]) && // lok_spk
                        is_string($row[1]) && // jenis
                        is_string($row[2]) // tipe
                    ) {
                        // Cek apakah lok_spk sudah ada di database
                        if (Barang::where('lok_spk', $row[0])->exists()) {
                            // Tambahkan error jika lok_spk sudah ada di database
                            $errors[] = "Row " . ($index + 1) . " has a duplicate lok_spk in database: ";
                            continue; // Lewati penyimpanan untuk row ini
                        }
            
                        // Simpan data ke database jika valid
                        $newBarang = Barang::create([
                            'lok_spk' => $row[0],
                            'jenis' => $row[1],
                            'tipe' => $row[2],
                            'kelengkapan' => $row[3],
                            'grade' => $row[4],
                            'nama_petugas' => $request->input('petugas'),
                            'dt_input' => Carbon::now(),
                            'user_id' => Auth::id(),
                            'gudang_id' => 6,
                            'status_barang' => 1,
                        ]);

                        if($newBarang){
                            $validLokSpk[] = [
                                'lok_spk' => $lokSpk,
                                'harga' => $row[5],
                                'alasan' => $row[6],
                                'pedagang' => $row[7],
                            ];
                        }
                    } else {
                        // Tambahkan error jika tidak valid
                        $errors[] = "Row " . ($index + 1) . " Gagal tambah barang";
                    }
                }
            } else {
                $errors[] = "Row " . ($index + 1) . ": Data tidak valid (Lok SPK atau harga jual kosong).";
            }
        }

        // Simpan data Faktur jika ada data valid
        if (!empty($validLokSpk)) {

            // Update Barang untuk lok_spk yang valid
            foreach ($validLokSpk as $item) {
                Barang::where('lok_spk', $item['lok_spk'])->update([
                    'status_barang' => 1,
                    'gudang_id' => 6,
                ]);

                ReturnBarang::create([
                    'lok_spk' => $item['lok_spk'],
                    't_return_id' => $request->input('t_return_id'),
                    'harga' => $item['harga'],
                    'alasan' => $item['alasan'],
                    'pedagang' => $item['pedagang'],
                ]);
            }

            // Tampilkan pesan sukses dan error
            return redirect()->back()
                ->with('success', 'Faktur berhasil disimpan. ' . count($validLokSpk) . ' barang diproses.')
                ->with('errors', $errors);
        }

        // Jika tidak ada data valid, hanya tampilkan error
        return redirect()->back()
            ->with('errors', $errors);
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
        // Validasi diperluas untuk data barang baru
        $validated = $request->validate([
            'id' => 'required|exists:t_return_barang,id',
            'lok_spk' => 'required|string',
            'harga' => 'required|numeric|min:0',
            'alasan' => 'nullable|string',
            'pedagang' => 'nullable|string',
            // Validasi untuk field baru, tapi boleh null karena tidak selalu diisi
            'jenis' => 'nullable|string',
            'tipe' => 'nullable|string',
            'kelengkapan' => 'nullable|string',
            'grade' => 'nullable|string',
        ]);

        try {
            DB::transaction(function () use ($validated, $request) {
                $transaksi = ReturnBarang::findOrFail($validated['id']);
                $originalLokSpk = $transaksi->lok_spk;
                $newLokSpk = $validated['lok_spk'];

                if ($originalLokSpk === $newLokSpk) {
                    $transaksi->update($validated);
                    return;
                }

                // Langkah 1: Proses LOK SPK LAMA
                if ($this->isLokSpkInUse($originalLokSpk)) {
                    Barang::where('lok_spk', $originalLokSpk)->update(['status_barang' => 2, 'gudang_id' => 0]);
                } else {
                    Barang::where('lok_spk', $originalLokSpk)->delete();
                }

                // Langkah 2: Proses LOK SPK BARU
                $barangBaru = Barang::where('lok_spk', $newLokSpk)->first();

                if ($barangBaru) {
                    // Jika LOK SPK BARU sudah ada
                    if ($barangBaru->status_barang != 2) {
                        throw new \Exception("Barang '$newLokSpk' tidak berstatus 'terjual' (status 2).");
                    }
                    $barangBaru->update(['status_barang' => 1, 'gudang_id' => 6]);
                } else {
                    // Jika LOK SPK BARU belum ada, BUAT BARU dengan data dari form
                    if (empty($validated['jenis']) || empty($validated['tipe'])) {
                        throw new \Exception("Jenis dan Tipe wajib diisi untuk barang baru.");
                    }
                    Barang::create([
                        'lok_spk' => $newLokSpk,
                        'jenis' => $validated['jenis'],
                        'tipe' => $validated['tipe'],
                        'kelengkapan' => $validated['kelengkapan'],
                        'grade' => $validated['grade'],
                        'nama_petugas' => Auth::user()->name,
                        'dt_input' => now(),
                        'user_id' => Auth::id(),
                        'gudang_id' => 6,
                        'status_barang' => 1,
                    ]);
                }

                // Langkah 3: Update transaksi di t_return_barang
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

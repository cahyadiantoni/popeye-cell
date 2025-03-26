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

class TransaksiReturnController extends Controller
{
    public function index()
    {
        // Mengambil semua transaksi return barang
        $returns = ReturnModel::with(['returnBarang'])->get()->map(function ($return) {
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

    public function destroyBarang($id){
        try {
            $transaksi = ReturnBarang::where('id', $id)->firstOrFail();

            // Mendapatkan lok_spk dari transaksi
            $lok_spk = $transaksi->lok_spk;

            // Melakukan update pada tabel Barang
            Barang::where('lok_spk', $lok_spk)->update([
                'status_barang' => 2,
                'gudang_id' => 0, 
            ]);

            // Hapus Transaksi
            $transaksi->delete();

            return redirect()->back()->with('success', 'Barang berhasil dihapus');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function updateBarang(Request $request){
        try {
            $validated = $request->validate([
                'id' => 'required|exists:t_return_barang,id',
                'lok_spk' => 'required|exists:t_return_barang,lok_spk',
                'harga' => 'required|numeric|min:0',
                'alasan' => 'string',
                'pedagang' => 'string',
            ]);
    
            // Gunakan firstOrFail() untuk pencarian berdasarkan 'id'
            $transaksi = ReturnBarang::where('id', $validated['id'])->firstOrFail();
            $transaksi->update(['harga' => $validated['harga'], 'alasan' => $validated['alasan'], 'pedagang' => $validated['pedagang']]);
    
            return redirect()->back()->with('success', 'Barang Return berhasil diupdate');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }


}

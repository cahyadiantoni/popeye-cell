<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\Gudang;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\ReturnBarang;
use App\Models\Kirim;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;

class TransaksiReturnController extends Controller
{
    public function index()
    {
        $returns = ReturnBarang::select(
                't_return.lok_spk',
                't_barang.tipe',
                DB::raw("
                    CASE 
                        WHEN t_jual.nomor_faktur IS NOT NULL THEN t_faktur.nomor_faktur
                        ELSE t_faktur_online.title
                    END AS nomor_faktur
                "),
                DB::raw("
                    CASE 
                        WHEN t_jual.nomor_faktur IS NOT NULL THEN t_faktur.pembeli
                        ELSE t_faktur_online.toko
                    END AS pembeli
                "),
                DB::raw("
                    CASE 
                        WHEN t_jual.nomor_faktur IS NOT NULL THEN t_faktur.tgl_jual
                        ELSE t_faktur_online.tgl_jual
                    END AS tgl_jual
                "),
                't_return.tgl_return',
                DB::raw("
                    CASE 
                        WHEN t_jual.harga IS NOT NULL THEN t_jual.harga
                        ELSE t_jual_online.harga
                    END AS harga_jual
                "),
                'users.name'
            )
            // Join ke Barang untuk mendapatkan tipe
            ->join('t_barang', 't_return.lok_spk', '=', 't_barang.lok_spk')
            // Left join ke transaksi offline
            ->leftJoin('t_jual', 't_return.lok_spk', '=', 't_jual.lok_spk')
            ->leftJoin('t_faktur', 't_jual.nomor_faktur', '=', 't_faktur.nomor_faktur')
            // Left join ke transaksi online
            ->leftJoin('t_jual_online', 't_return.lok_spk', '=', 't_jual_online.lok_spk')
            ->leftJoin('t_faktur_online', 't_jual_online.faktur_online_id', '=', 't_faktur_online.id')
            // Join ke User
            ->join('users', 't_return.user_id', '=', 'users.id')
            ->orderByDesc('t_return.tgl_return')
            ->get();
    
        return view('pages.transaksi-return.index', compact('returns'));
    }    

    public function returnBarang(Request $request)
    {
        // Ambil data lok_spk, pengirim_gudang_id, penerima_gudang_id yang diceklis
        $lok_spks = $request->input('lok_spk');
        $gudangPenerimaId = $request->input('gudang_id');
        $gudangPenerima = Gudang::find($gudangPenerimaId);
        $pj_gudang = $gudangPenerima->pj_gudang;
        
        // Mendapatkan auth id pengguna yang sedang login
        $authId = Auth::id();
        $gudang = Gudang::where('pj_gudang', $authId)->select('id', 'nama_gudang')->first();
        $gudangIds = $gudang->id; 

        // Ambil data tgl_return yang dikirimkan dari form
        $tglReturn = $request->input('tgl_return'); // tanggal return

        // Pastikan tombol "Terima" atau "Tolak" diklik
        if ($request->input('action') == 'return') {
            // Logika untuk menerima permintaan
            foreach ($lok_spks as $index => $lok_spk) {

                // Simpan data return barang ke tabel t_return
                ReturnBarang::create([
                    'lok_spk' => $lok_spk,
                    'tgl_return' => $tglReturn,  // Menyimpan tanggal return
                    'user_id' => Auth::id(),    // Mendapatkan ID pengguna yang login
                ]);

                // Update data di model Barang
                Barang::where('lok_spk', $lok_spk)->update([
                    'status_barang' => 0,
                ]);
            }

            return redirect()->back()->with('success', 'Permintaan diterima dan barang berhasil di-return.');
        } 

        return redirect()->back()->with('error', 'Tidak ada aksi yang dilakukan.');
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
                    if (in_array($barang->status_barang, [0, 1, 2])) {
    
                        // Simpan lok_spk untuk update nanti
                        $validLokSpk[] = [
                            'lok_spk' => $lokSpk,
                        ];
                    } else {
                        $errors[] = "Row " . ($index + 1) . ": Lok SPK '$lokSpk' memiliki status_barang yang tidak sesuai.";
                    }
                } else {
                    $errors[] = "Row " . ($index + 1) . ": Lok SPK '$lokSpk' tidak ditemukan.";
                }
            } else {
                $errors[] = "Row " . ($index + 1) . ": Data tidak valid (Lok SPK kosong).";
            }
        }
    
        // Simpan data Faktur jika ada data valid
        if (!empty($validLokSpk)) {
            
            // Update Barang untuk lok_spk yang valid
            foreach ($validLokSpk as $item) {
                ReturnBarang::create([
                    'lok_spk' => $item['lok_spk'],
                    'tgl_return' => now(),  // Menyimpan tanggal return
                    'user_id' => Auth::id(),    // Mendapatkan ID pengguna yang login
                ]);

                Barang::where('lok_spk', $item['lok_spk'])->update([
                    'status_barang' => 1,
                    'gudang_id' => 6, // Update harga_jual dari Excel
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

    public function destroy($lok_spk)
    {
        try {
            $return = ReturnBarang::where('lok_spk', $lok_spk)->firstOrFail();

            Barang::where('lok_spk', $lok_spk)->update([
                'status_barang' => 2,
            ]);

            // Hapus Return
            $return->delete();

            return redirect()->back()->with('success', 'Barang berhasil dihapus');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

}

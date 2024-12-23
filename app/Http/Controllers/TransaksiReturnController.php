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

class TransaksiReturnController extends Controller
{
    public function index()
    {
        
        $returns = ReturnBarang::with(['user', 'barang.faktur'])->get()->sortByDesc('tgl_return');;
          

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

                // Logika untuk mengirim barang
                Kirim::create([
                    'lok_spk' => $lok_spk,
                    'pengirim_gudang_id' => $gudangIds,
                    'penerima_gudang_id' => $gudangPenerimaId,
                    'pengirim_user_id' => Auth::id(),
                    'penerima_user_id' => $pj_gudang,
                    'status' => 0,
                    'dt_kirim' => Carbon::now(),
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

}

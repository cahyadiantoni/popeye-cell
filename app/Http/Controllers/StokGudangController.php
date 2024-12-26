<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\Gudang;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Kirim;
use Illuminate\Support\Facades\Auth;


class StokGudangController extends Controller
{
    public function request_masuk()
    {
        // Mendapatkan auth id pengguna yang sedang login
        $authId = Auth::id();

        // Mengambil data dari model Kirim dengan filter dan relasi
        $requests = Kirim::with([
            'pengirimGudang:id,nama_gudang',
            'penerimaGudang:id,nama_gudang',
            'pengirimUser:id,name',
            'penerimaUser:id,name',
            'barang:lok_spk,tipe'
        ])
        ->where('penerima_user_id', $authId)
        ->where('status', 0)
        ->get();


        return view('pages.stok-gudang.request_barang_masuk', compact('requests'));
    }

    public function history_kirim()
    {

        // Mengambil data dari model Kirim dengan filter dan relasi
        $requests = Kirim::with([
            'pengirimGudang:id,nama_gudang',
            'penerimaGudang:id,nama_gudang',
            'pengirimUser:id,name',
            'penerimaUser:id,name',
            'barang:lok_spk,tipe'
        ])
        ->orderBy('updated_at', 'desc')
        ->get();


        return view('pages.stok-gudang.history_kirim', compact('requests'));
    }

    public function choice_gudang()
    {
        $allgudangs = Gudang::all();

        // Mengirim data gudangs dan barangs ke view
        return view('pages.stok-gudang.choice_gudang', compact('allgudangs'));
    }

    public function stok_opname(Request $request)
    {
        // Ambil ID gudang dari query string, default 'all' jika tidak ada
        $gudangId = $request->query('gudang_id', 'all');
    
        // Ambil semua gudang
        $allgudangs = Gudang::all();
    
        if ($gudangId === 'all') {
            $selectedGudang = (object) ['nama_gudang' => 'Semua Gudang'];
            $barangs = Barang::with('gudang')
                ->where('status_barang', 1) // Menambahkan kondisi untuk status
                ->get();
        } else {
            // Validasi dan ambil data gudang yang dipilih
            $selectedGudang = Gudang::findOrFail($gudangId);
    
            // Ambil data barang terkait gudang
            $barangs = Barang::with('gudang')
                ->where('gudang_id', $gudangId)
                ->where('status_barang', 1)
                ->get();
        }
    
        // Kirim data ke view
        return view('pages.stok-gudang.stok_opname', compact('selectedGudang', 'barangs', 'allgudangs'));
    }    
    

    public function handleRequest(Request $request)
    {
        // Ambil data lok_spk, pengirim_gudang_id, dan penerima_gudang_id yang diceklis
        $ids = $request->input('id');
        $lok_spks = $request->input('lok_spk');
        $pengirimGudangIds = $request->input('pengirim_gudang_id');
        $penerimaGudangIds = $request->input('penerima_gudang_id');

        // Pastikan tombol "Terima" atau "Tolak" diklik
        if ($request->input('action') == 'terima') {
            // Logika untuk menerima permintaan
            foreach ($ids as $index => $id) {
                $lok_spk = $lok_spks[$index];
                $pengirimGudangId = $pengirimGudangIds[$index];
                $penerimaGudangId = $penerimaGudangIds[$index];

                // Update data di model Kirim
                Kirim::where('id', $id)->update([
                    'status' => 1,
                    'dt_terima' => Carbon::now(),
                ]);

                // Update data di model Barang
                Barang::where('lok_spk', $lok_spk)->update([
                    'gudang_id' => $penerimaGudangId,
                    'status_barang' => 1,
                ]);
            }
            return redirect()->back()->with('success', 'Permintaan diterima.');
        } elseif ($request->input('action') == 'tolak') {
            // Logika untuk menolak permintaan
            foreach ($ids as $index => $id) {
                $lok_spk = $lok_spks[$index];
                $pengirimGudangId = $pengirimGudangIds[$index];
                $penerimaGudangId = $penerimaGudangIds[$index];

                // Update data di model Kirim
                Kirim::where('id', $id)->update([
                    'status' => 2,
                    'dt_terima' => Carbon::now(),
                ]);

                // Update data di model Barang
                Barang::where('lok_spk', $lok_spk)->update([
                    'gudang_id' => $pengirimGudangId,
                    'status_barang' => 1,
                ]);
            }
            return redirect()->back()->with('success', 'Permintaan ditolak.');
        }

        return redirect()->back()->with('error', 'Tidak ada aksi yang dilakukan.');
    }

    public function kirimBarang(Request $request)
    {
        // Ambil data lok_spk, pengirim_gudang_id, dan penerima_gudang_id yang diceklis
        $lok_spks = $request->input('lok_spk');
        $gudangPenerimaId = $request->input('gudang_id');
        $gudangPenerima = Gudang::find($gudangPenerimaId);
        $pj_gudang = $gudangPenerima->pj_gudang;
        // Mendapatkan auth id pengguna yang sedang login
        $authId = Auth::id();
        $gudang = Gudang::where('pj_gudang', $authId)->select('id', 'nama_gudang')->first();
        $gudangIds = $gudang->id; 

        // Pastikan tombol "Terima" atau "Tolak" diklik
        if ($request->input('action') == 'kirim') {
            // Logika untuk menerima permintaan
            foreach ($lok_spks as $index => $lok_spk) {

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
            return redirect()->back()->with('success', 'Permintaan diterima.');
        } 

        return redirect()->back()->with('error', 'Tidak ada aksi yang dilakukan.');
    }


}

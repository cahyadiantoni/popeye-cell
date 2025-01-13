<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\Gudang;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Kirim;
use App\Models\KirimBarang;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class TerimaBarangController extends Controller
{
    public function index()
    {
        // Mendapatkan auth id pengguna yang sedang login
        $authId = Auth::id();

        // Mengambil data dari model 
        $requests = Kirim::where('penerima_user_id', $authId)
        ->orderBy('status')
        ->orderBy('dt_kirim')
        ->get();

        return view('pages.terima-barang.index', compact('requests', )); 
    }

    public function show($id)
    {
        // Ambil data faktur berdasarkan nomor faktur
        $kirim = Kirim::where('id', $id)
            ->firstOrFail();

        // Ambil data barang yang berhubungan dengan transaksi jual
        $kirimBarangs = KirimBarang::with('barang')
            ->where('kirim_id', $id)
            ->get();

        return view('pages.terima-barang.detail', compact('kirim', 'kirimBarangs'));
    }  

    public function terima(Request $request)
    {
        $lokSpks = explode(',', $request->lok_spks); // Daftar lok_spk
        $gudangId = $request->gudang_id;

        // Update barang berdasarkan lok_spk
        Barang::whereIn('lok_spk', $lokSpks)->update(['gudang_id' => $gudangId]);

        // Update status kirim
        Kirim::where('id', $request->kirim_id)->update(['status' => 1, 'dt_terima' => now()]);

        return redirect()->back()->with('success', 'Barang berhasil diterima.');
    }

    public function tolak(Request $request)
    {
        // Update status kirim
        Kirim::where('id', $request->kirim_id)->update(['status' => 2, 'dt_terima' => now()]);

        return redirect()->back()->with('success', 'Barang berhasil ditolak.');
    }

}

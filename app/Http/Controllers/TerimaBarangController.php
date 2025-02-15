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
use App\Exports\TerimaBarangExport;

class TerimaBarangController extends Controller
{
    public function index()
    {
        $gudangId = Auth::user()->gudang_id;

        // Mengambil data dari model 
        $requests = Kirim::where('penerima_gudang_id', $gudangId)
            ->orderBy('status')
            ->orderBy('dt_kirim')
            ->get();

        $jumlahBarang = KirimBarang::selectRaw('kirim_id, COUNT(*) as jumlah')
            ->groupBy('kirim_id')
            ->pluck('jumlah', 'kirim_id');

        return view('pages.terima-barang.index', compact('requests', 'jumlahBarang' )); 
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

        $jumlahBarang = $kirimBarangs->count();

        return view('pages.terima-barang.detail', compact('kirim', 'kirimBarangs', 'jumlahBarang'));
    }  

    public function terima(Request $request)
    {
        $lokSpks = explode(',', $request->lok_spks); // Daftar lok_spk
        $gudangId = $request->gudang_id;
        $authId = Auth::id();

        // Update barang berdasarkan lok_spk
        Barang::whereIn('lok_spk', $lokSpks)->update(['gudang_id' => $gudangId]);

        // Update status kirim
        Kirim::where('id', $request->kirim_id)->update(['penerima_user_id' => $authId, 'status' => 1, 'dt_terima' => now()]);

        return redirect()->back()->with('success', 'Barang berhasil diterima.');
    }

    public function tolak(Request $request)
    {
        $authId = Auth::id();

        // Update status kirim
        Kirim::where('id', $request->kirim_id)->update(['penerima_user_id' => $authId, 'status' => 2, 'dt_terima' => now()]);

        return redirect()->back()->with('success', 'Barang berhasil ditolak.');
    }

    public function export(Request $request, $id)
    {
        $today = now()->format('Y-m-d'); // Format tanggal menjadi 'YYYY-MM-DD'
        $fileName = "request-barang-masuk-{$today}.xlsx";

        // Pass $id ke KirimBarangExport
        return Excel::download(new TerimaBarangExport($id), $fileName);
    }

}

<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\Gudang;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Kirim;
use App\Models\Faktur;
use App\Models\TransaksiJual;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class TransaksiFakturController extends Controller
{
    public function index()
    {
        $fakturs = Faktur::withCount(['barangs as total_barang'])->get();

        return view('pages.transaksi-faktur.index', compact('fakturs')); 
    }

    public function show($nomor_faktur)
    {
        // Ambil data faktur berdasarkan nomor faktur
        $faktur = Faktur::with('barangs')
            ->where('nomor_faktur', $nomor_faktur)
            ->firstOrFail();

        // Ambil data barang yang berhubungan dengan transaksi jual
        $transaksiJuals = TransaksiJual::with('barang')
            ->where('nomor_faktur', $nomor_faktur)
            ->get();

        return view('pages.transaksi-faktur.detail', compact('faktur', 'transaksiJuals'));
    }
}

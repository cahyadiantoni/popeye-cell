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

    public function printPdf($nomor_faktur)
    {
        // Ambil data faktur dan transaksi jual
        $faktur = Faktur::with('barangs')
            ->where('nomor_faktur', $nomor_faktur)
            ->firstOrFail();

        $transaksiJuals = TransaksiJual::with('barang')
            ->where('nomor_faktur', $nomor_faktur)
            ->get();

        // Hitung subtotal tiap barang
        $subtotalKumulatif = 0; // Variabel untuk menyimpan subtotal kumulatif

        $transaksiJuals->map(function ($transaksi) use (&$subtotalKumulatif) {
            $subtotalKumulatif += $transaksi->harga; // Tambahkan harga pada baris ini
            $transaksi->subtotal = $subtotalKumulatif; // Tetapkan subtotal kumulatif sebagai subtotal
            return $transaksi;
        });
        

        // Total keseluruhan
        $totalHarga = $transaksiJuals->sum('harga');

        // Kirim data ke template PDF
        $pdf = \PDF::loadView('pages.transaksi-faktur.print', compact('faktur', 'transaksiJuals', 'totalHarga'));

        // Unduh atau tampilkan PDF
        return $pdf->stream('Faktur_Penjualan_' . $faktur->nomor_faktur . '.pdf');
    }

    public function update(Request $request, $nomor_faktur)
    {
        try {
            // Validasi data input
            $validated = $request->validate([
                'pembeli' => 'required|string|max:255',
                'tgl_jual' => 'required|date',
                'petugas' => 'required|string|max:255',
                'keterangan' => 'nullable|string',
            ]);
    
            // Cari faktur berdasarkan nomor faktur
            $faktur = Faktur::where('nomor_faktur', $nomor_faktur)->firstOrFail();
    
            // Update data faktur
            $faktur->update([
                'pembeli' => $validated['pembeli'],
                'tgl_jual' => $validated['tgl_jual'],
                'petugas' => $validated['petugas'],
                'keterangan' => $validated['keterangan'],
            ]);
    
            // Flash session message
            session()->flash('success', 'Faktur berhasil diupdate');
            return redirect()->route('transaksi-faktur.index');
        } catch (\Exception $e) {
            // Flash session message on failure
            session()->flash('error', 'Terjadi kesalahan: ' . $e->getMessage());
            return redirect()->back();
        }
    }    
}

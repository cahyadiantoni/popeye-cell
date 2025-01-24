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
        $fakturs = Faktur::withCount(['barangs as total_barang'])
                     ->orderBy('tgl_jual', 'desc')
                     ->get();

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

    public function addbarang(Request $request)
    {
        $request->validate([
            'filedata' => 'required|file|mimes:xlsx,xls',
            'total' => 'required',
            'nomor_faktur' => 'required',
        ]);

        // Inisialisasi variabel
        $errors = [];
        $totalHargaJual = $request->input('total');
        $validLokSpk = [];
        $processedLokSpk = []; // Untuk memeriksa duplikat di file Excel

        // Membaca file Excel
        $file = $request->file('filedata');
        $data = Excel::toArray([], $file);

        foreach ($data[0] as $index => $row) {
            // Lewati baris pertama jika merupakan header
            if ($index === 0) continue;

            // Validasi kolom di Excel
            if (isset($row[0]) && isset($row[1])) {
                $lokSpk = $row[0]; // Lok SPK
                $hargaJual = $row[1] * 1000; // Harga Jual

                // Cek duplikat lok_spk di dalam file Excel
                if (in_array($lokSpk, $processedLokSpk)) {
                    $errors[] = "Row " . ($index + 1) . ": Lok SPK '$lokSpk' duplikat di dalam file Excel.";
                    continue;
                }

                // Tambahkan lok_spk ke daftar yang sudah diproses
                $processedLokSpk[] = $lokSpk;

                // Cek duplikat kombinasi lok_spk dan nomor_faktur di database
                $existsInDatabase = TransaksiJual::where('lok_spk', $lokSpk)
                    ->where('nomor_faktur', $request->input('nomor_faktur'))
                    ->exists();

                if ($existsInDatabase) {
                    $errors[] = "Row " . ($index + 1) . ": Lok SPK '$lokSpk' dengan Nomor Faktur '{$request->input('nomor_faktur')}' sudah ada di database.";
                    continue;
                }

                // Cari barang berdasarkan lok_spk
                $barang = Barang::where('lok_spk', $lokSpk)->first();

                if ($barang) {
                    // Cek apakah status_barang adalah 0 atau 1
                    if (in_array($barang->status_barang, [0, 1])) {
                        // Tambahkan harga_jual ke total
                        $totalHargaJual += $hargaJual;

                        // Simpan lok_spk untuk update nanti
                        $validLokSpk[] = [
                            'lok_spk' => $lokSpk,
                            'harga_jual' => $hargaJual,
                        ];
                    } else {
                        $errors[] = "Row " . ($index + 1) . ": Lok SPK '$lokSpk' memiliki status_barang yang tidak sesuai.";
                    }
                } else {
                    $errors[] = "Row " . ($index + 1) . ": Lok SPK '$lokSpk' tidak ditemukan.";
                }
            } else {
                $errors[] = "Row " . ($index + 1) . ": Data tidak valid (Lok SPK atau harga jual kosong).";
            }
        }

        // Simpan data Faktur jika ada data valid
        if (!empty($validLokSpk)) {
            Faktur::where('nomor_faktur', $request->input('nomor_faktur'))
                ->update([
                    'total' => $totalHargaJual,
                ]);

            // Update Barang untuk lok_spk yang valid
            foreach ($validLokSpk as $item) {
                Barang::where('lok_spk', $item['lok_spk'])->update([
                    'status_barang' => 2,
                    'no_faktur' => $request->input('nomor_faktur'),
                    'harga_jual' => $item['harga_jual'], // Update harga_jual dari Excel
                ]);

                TransaksiJual::create([
                    'lok_spk' => $item['lok_spk'],
                    'nomor_faktur' => $request->input('nomor_faktur'),
                    'harga' => $item['harga_jual'],
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
}

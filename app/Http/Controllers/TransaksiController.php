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

class TransaksiController extends Controller
{
    public function index()
    {
        
        $barangs = Barang::join('t_faktur', 't_barang.no_faktur', '=', 't_faktur.nomor_faktur')
        ->select(
            't_barang.lok_spk',
            't_barang.tipe',
            't_barang.no_faktur',
            't_barang.harga_jual',
            't_barang.status_barang',
            't_faktur.*'
        )
        ->where('t_barang.status_barang', 2)
        ->orderBy('t_faktur.tgl_jual', 'desc')
        ->get();   

        $allgudangs = Gudang::all();


        return view('pages.transaksi-jual.index', compact('barangs', 'allgudangs'));
    }

    public function create()
    {
        $Userid = Auth::user()->id;
        $SugestNoFak = null;

        if ($Userid == 8 || $Userid == 6) {
            // Tentukan prefix berdasarkan User ID
            $prefix = $Userid == 8 ? "VR 0" : "AT ";
        
            // Ambil faktur dengan format yang sesuai
            $lastFaktur = Faktur::where('nomor_faktur', 'like', $prefix . '%')
                ->orderByRaw("CAST(SUBSTRING(nomor_faktur, " . (strlen($prefix) + 1) . ", LENGTH(nomor_faktur) - " . strlen($prefix) . ") AS UNSIGNED) DESC")
                ->first();
        
            if ($lastFaktur) {
                // Ambil angka tertinggi dari nomor faktur dengan regex
                preg_match('/' . preg_quote($prefix, '/') . '(\d+)/', $lastFaktur->nomor_faktur, $matches);
                $lastNumber = isset($matches[1]) ? (int) $matches[1] : 0;
                $newNumber = $lastNumber + 1;
            } else {
                $newNumber = 1; // Jika belum ada faktur, mulai dari 1
            }
        
            $SugestNoFak = $prefix . $newNumber;
        }
        
        return view('pages.transaksi-jual.create', compact('SugestNoFak'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'filedata' => 'required|file|mimes:xlsx,xls',
            'tgl_jual' => 'required|date',
            'nomor_faktur' => 'required|string',
            'pembeli' => 'required|string',
            'petugas' => 'required|string',
            'grade' => 'required|string',
        ]);

        // Cek apakah nomor_faktur sudah ada di tabel Faktur
        $existingFaktur = Faktur::where('nomor_faktur', $request->input('nomor_faktur'))->exists();
        if ($existingFaktur) {
            return redirect()->back()->with('error', 'Gagal disimpan: Nomor Faktur sudah ada. Harap diganti!');
        }

        // Inisialisasi variabel
        $errors = [];
        $totalHargaJual = 0;
        $validLokSpk = [];
        $processedLokSpk = []; // Array untuk memeriksa duplikat lok_spk

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

                // Cek apakah lok_spk sudah ada di file Excel (duplikat dalam satu kali store)
                if (in_array($lokSpk, $processedLokSpk)) {
                    $errors[] = "Row " . ($index + 1) . ": Lok SPK '$lokSpk' duplikat di dalam file Excel.";
                    continue;
                }

                // Tambahkan lok_spk ke daftar yang sudah diproses
                $processedLokSpk[] = $lokSpk;

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
            Faktur::create([
                'nomor_faktur' => $request->input('nomor_faktur'),
                'pembeli' => $request->input('pembeli'),
                'tgl_jual' => $request->input('tgl_jual'),
                'petugas' => $request->input('petugas'),
                'grade' => $request->input('grade'),
                'keterangan' => $request->input('keterangan'),
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
            return redirect()->route('transaksi-faktur.show', ['nomor_faktur' => $request->input('nomor_faktur')])
                ->with('success', 'Faktur berhasil disimpan. ' . count($validLokSpk) . ' barang diproses.')
                ->with('errors', $errors);
        }

        // Jika tidak ada data valid, hanya tampilkan error
        return redirect()->back()->with('errors', $errors);
    }
    

    public function destroy($lok_spk)
    {
        try {
            $transaksi = TransaksiJual::where('lok_spk', $lok_spk)->firstOrFail();

            Barang::where('lok_spk', $lok_spk)->update([
                'status_barang' => 1,
                'no_faktur' => null,
                'harga_jual' => 0, 
            ]);

            // Hapus Transaksi
            $nomorFaktur = $transaksi->nomor_faktur;
            $transaksi->delete();

            // Hitung ulang total pada Faktur
            $totalBaru = TransaksiJual::where('nomor_faktur', $nomorFaktur)->sum('harga');
            Faktur::where('nomor_faktur', $nomorFaktur)->update(['total' => $totalBaru]);

            return redirect()->back()->with('success', 'Barang berhasil dihapus');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }


    public function update(Request $request)
    {
        try {
            $validated = $request->validate([
                'lok_spk' => 'required|exists:t_jual,lok_spk',
                'harga' => 'required|numeric|min:0',
            ]);
    
            // Gunakan firstOrFail() untuk pencarian berdasarkan 'lok_spk'
            $transaksi = TransaksiJual::where('lok_spk', $validated['lok_spk'])->firstOrFail();
            $transaksi->update(['harga' => $validated['harga']]);
    
            // Update harga_jual pada model Barang
            $barang = $transaksi->barang;
            $barang->update(['harga_jual' => $validated['harga']]);
    
            // Hitung ulang total pada Faktur
            $nomorFaktur = $transaksi->nomor_faktur;
            $totalBaru = TransaksiJual::where('nomor_faktur', $nomorFaktur)->sum('harga');
            Faktur::where('nomor_faktur', $nomorFaktur)->update(['total' => $totalBaru]);
    
            return redirect()->back()->with('success', 'Harga berhasil diupdate');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
    
    

}

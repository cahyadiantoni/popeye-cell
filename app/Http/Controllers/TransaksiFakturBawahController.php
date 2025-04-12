<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\FakturBukti;
use App\Models\Gudang;
use App\Models\Negoan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Kirim;
use App\Models\FakturBawah;
use App\Models\TransaksiJualBawah;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

class TransaksiFakturBawahController extends Controller
{
    public function index(Request $request)
    {
        $query = FakturBawah::withCount(['barangs as total_barang'])
            ->orderBy('tgl_jual', 'desc');
    
        $roleUser = optional(Auth::user())->role;
        $gudangId = optional(Auth::user())->gudang_id;
    
        // Filter berdasarkan rentang tanggal
        if ($request->filled('tanggal_mulai') && $request->filled('tanggal_selesai')) {
            $query->whereBetween('tgl_jual', [$request->tanggal_mulai, $request->tanggal_selesai]);
        }
    
        $fakturs = $query->get();
    
        return view('pages.transaksi-faktur-bawah.index', compact('fakturs', 'roleUser'));
    }    

    public function show($nomor_faktur)
    {
        // Ambil data faktur berdasarkan nomor faktur
        $faktur = FakturBawah::with('barangs')
            ->where('nomor_faktur', $nomor_faktur)
            ->firstOrFail();

        // Ambil data barang yang berhubungan dengan transaksi jual
        $transaksiJuals = TransaksiJualBawah::with('barang')
            ->where('nomor_faktur', $nomor_faktur)
            ->get();

        $roleUser = optional(Auth::user())->role;

        return view('pages.transaksi-faktur-bawah.detail', compact('faktur', 'transaksiJuals', 'roleUser'));
    }

    public function printPdf($nomor_faktur)
    {
        // Ambil data faktur dan transaksi jual
        $faktur = FakturBawah::with('barangs')
            ->where('nomor_faktur', $nomor_faktur)
            ->firstOrFail();

        $transaksiJuals = TransaksiJualBawah::with('barang')
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
        $pdf = \PDF::loadView('pages.transaksi-faktur-bawah.print', compact('faktur', 'transaksiJuals', 'totalHarga'));

        // Unduh atau tampilkan PDF
        return $pdf->stream('Faktur_Penjualan_' . $faktur->nomor_faktur . '.pdf');
    }

    public function update(Request $request, $id)
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
            $faktur = FakturBawah::where('id', $id)->firstOrFail();

            // Update data faktur
            $faktur->update([
                'pembeli' => $validated['pembeli'],
                'tgl_jual' => $validated['tgl_jual'],
                'petugas' => $validated['petugas'],
                'keterangan' => $validated['keterangan'],
            ]);
    
            // Flash session message
            session()->flash('success', 'FakturBawah berhasil diupdate');
            return redirect()->route('transaksi-faktur-bawah.index');
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
                $existsInDatabase = TransaksiJualBawah::where('lok_spk', $lokSpk)
                    ->where('nomor_faktur', $request->input('nomor_faktur'))
                    ->exists();

                if ($existsInDatabase) {
                    $errors[] = "Row " . ($index + 1) . ": Lok SPK '$lokSpk' dengan Nomor FakturBawah '{$request->input('nomor_faktur')}' sudah ada di database.";
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

        // Simpan data FakturBawah jika ada data valid
        if (!empty($validLokSpk)) {
            FakturBawah::where('nomor_faktur', $request->input('nomor_faktur'))
                ->update([
                    'total' => $totalHargaJual,
                ]);

            // Update Barang untuk lok_spk yang valid
            foreach ($validLokSpk as $item) {
                $tipe = Barang::where('lok_spk', $item['lok_spk'])
               ->pluck('tipe')
               ->first();
                
                $grade = $request->input('grade');

                $negoan = Negoan::where('tipe', $tipe)
                        ->where('grade', $grade)
                        ->where('status', 1)
                        ->orderBy('updated_at', 'desc')
                        ->first();

                Barang::where('lok_spk', $item['lok_spk'])->update([
                    'status_barang' => 2,
                    'no_faktur' => $request->input('nomor_faktur'),
                    'harga_jual' => $item['harga_jual'], // Update harga_jual dari Excel
                ]);

                TransaksiJualBawah::create([
                    'lok_spk' => $item['lok_spk'],
                    'nomor_faktur' => $request->input('nomor_faktur'),
                    'harga' => $item['harga_jual'],
                    'harga_acc' => $negoan->harga_acc ?? 0,
                ]);
            }

            // Tampilkan pesan sukses dan error
            return redirect()->back()
                ->with('success', 'FakturBawah berhasil disimpan. ' . count($validLokSpk) . ' barang diproses.')
                ->with('errors', $errors);
        }

        // Jika tidak ada data valid, hanya tampilkan error
        return redirect()->back()
            ->with('errors', $errors);
    }

    public function destroy($nomor_faktur)
    {
        try {
            // Cari faktur berdasarkan nomor_faktur
            $faktur = FakturBawah::where('nomor_faktur', $nomor_faktur)->firstOrFail();
    
            // Ambil data lok_spk dari TransaksiJualBawah berdasarkan nomor_faktur
            $lokSpkList = TransaksiJualBawah::where('nomor_faktur', $nomor_faktur)->pluck('lok_spk');
    
            // Hapus semua baris di TransaksiJualBawah yang memiliki nomor_faktur tersebut
            TransaksiJualBawah::where('nomor_faktur', $nomor_faktur)->delete();
    
            // Update data pada tabel Barang
            Barang::whereIn('lok_spk', $lokSpkList)
                ->update([
                    'status_barang' => 1,
                    'no_faktur' => null,
                    'harga_jual' => 0,
                ]);
    
            // Hapus FakturBawah
            $faktur->delete();
    
            return redirect()->back()->with('success', 'FakturBawah dan data terkait berhasil dihapus');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function tandaiSudahDicek($id)
    {
        try {
            // Cari faktur berdasarkan nomor_faktur
            $faktur = FakturBawah::where('id', $id)->firstOrFail();

            $faktur->is_finish = 1;
            $faktur->save();
    
            return redirect()->back()->with('success', 'FakturBawah ditandai sudah selesai');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
}

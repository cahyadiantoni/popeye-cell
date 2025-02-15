<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\Gudang;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Kirim;
use App\Models\FakturOnline;
use App\Models\TransaksiJualOnline;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

class TransaksiFakturOnlineController extends Controller
{
    public function index()
    {
        $fakturs = FakturOnline::withCount(['barangs as total_barang'])
                     ->orderBy('tgl_jual', 'desc')
                     ->get();

        $roleUser = optional(Auth::user())->role;

        return view('pages.transaksi-faktur-online.index', compact('fakturs', 'roleUser')); 
    }

    public function show($nomor_faktur)
    {
        // Ambil data faktur berdasarkan nomor faktur
        $faktur = FakturOnline::with('barangs')
            ->where('id', $nomor_faktur)
            ->firstOrFail();

        // Ambil data barang yang berhubungan dengan transaksi jual
        $transaksiJuals = TransaksiJualOnline::with('barang')
            ->where('faktur_online_id', $nomor_faktur)
            ->get();

        $roleUser = optional(Auth::user())->role;

        return view('pages.transaksi-faktur-online.detail', compact('faktur', 'transaksiJuals', 'roleUser'));
    }

    public function printPdf($nomor_faktur)
    {
        // Ambil data faktur dan transaksi jual
        $faktur = FakturOnline::with('barangs')
            ->where('id', $nomor_faktur)
            ->firstOrFail();

        $transaksiJuals = TransaksiJualOnline::with('barang')
            ->where('faktur_online_id', $nomor_faktur)
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
        $pdf = \PDF::loadView('pages.transaksi-faktur-online.print', compact('faktur', 'transaksiJuals', 'totalHarga'));

        // Unduh atau tampilkan PDF
        return $pdf->stream('Faktur_Penjualan_Online_' . $faktur->id . '.pdf');
    }

    public function update(Request $request, $nomor_faktur)
    {
        try {
            // Validasi data input
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'toko' => 'required|string|max:255',
                'tgl_jual' => 'required|date',
                'petugas' => 'required|string|max:255',
                'grade' => 'required',
                'keterangan' => 'nullable|string',
            ]);
    
            // Cari faktur berdasarkan nomor faktur
            $faktur = FakturOnline::where('id', $nomor_faktur)->firstOrFail();
    
            // Update data faktur
            $faktur->update([
                'title' => $validated['title'],
                'toko' => $validated['toko'],
                'tgl_jual' => $validated['tgl_jual'],
                'petugas' => $validated['petugas'],
                'grade' => $validated['grade'],
                'keterangan' => $validated['keterangan'],
            ]);
    
            // Flash session message
            session()->flash('success', 'Faktur berhasil diupdate');
            return redirect()->route('transaksi-faktur-online.index');
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
            'faktur_online_id' => 'required',
        ]);
    
        // Inisialisasi variabel
        $errors = [];
        $totalHargaJual = $request->input('total');
        $validLokSpk = [];
        $processedLokSpk = []; // Untuk memeriksa duplikat di dalam Excel
    
        // Membaca file Excel
        $file = $request->file('filedata');
        $data = Excel::toArray([], $file);
    
        foreach ($data[0] as $index => $row) {
            // Lewati baris pertama jika merupakan header
            if ($index === 0) continue;
    
            // Validasi kolom di Excel
            if (isset($row[0]) && isset($row[1]) && isset($row[2]) && isset($row[3])) {
                $invoice = $row[0];
                $lokSpk = $row[1]; // Lok SPK
                $hargaJual = $row[2] * 1000; // Harga Jual
                $pj = $row[3] * 1000;
    
                // Cek duplikat `lok_spk` di dalam file Excel
                if (in_array($lokSpk, $processedLokSpk)) {
                    $errors[] = "Row " . ($index + 1) . ": Lok SPK '$lokSpk' duplikat di dalam file Excel.";
                    continue;
                }
    
                // Tambahkan `lok_spk` ke daftar yang sudah diproses
                $processedLokSpk[] = $lokSpk;
    
                // Cek apakah `lok_spk` dan `faktur_online_id` sudah ada di database
                $exists = TransaksiJualOnline::where('lok_spk', $lokSpk)
                    ->where('faktur_online_id', $request->input('faktur_online_id'))
                    ->exists();
    
                if ($exists) {
                    $errors[] = "Row " . ($index + 1) . ": Lok SPK '$lokSpk' dengan Faktur ID '{$request->input('faktur_online_id')}' sudah ada di database.";
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
                            'invoice' => $invoice,
                            'pj' => $pj,
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
            FakturOnline::where('id', $request->input('faktur_online_id'))
                ->update([
                    'total' => $totalHargaJual,
                ]);
    
            // Update Barang untuk lok_spk yang valid
            foreach ($validLokSpk as $item) {
                Barang::where('lok_spk', $item['lok_spk'])->update([
                    'status_barang' => 2,
                    'no_faktur' => $request->input('faktur_online_id'),
                    'harga_jual' => $item['harga_jual'], // Update harga_jual dari Excel
                ]);
    
                TransaksiJualOnline::create([
                    'lok_spk' => $item['lok_spk'],
                    'faktur_online_id' => $request->input('faktur_online_id'),
                    'harga' => $item['harga_jual'],
                    'invoice' => $item['invoice'],
                    'pj' => $item['pj'],
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

    public function destroy($nomor_faktur)
    {
        try {
            // Cari faktur berdasarkan nomor_faktur
            $faktur = FakturOnline::where('id', $nomor_faktur)->firstOrFail();
    
            // Ambil data lok_spk dari TransaksiJual berdasarkan nomor_faktur
            $lokSpkList = TransaksiJualOnline::where('faktur_online_id', $nomor_faktur)->pluck('lok_spk');
    
            // Hapus semua baris di TransaksiJual yang memiliki nomor_faktur tersebut
            TransaksiJualOnline::where('faktur_online_id', $nomor_faktur)->delete();
    
            // Update data pada tabel Barang
            Barang::whereIn('lok_spk', $lokSpkList)
                ->update([
                    'status_barang' => 1,
                    'no_faktur' => null,
                    'harga_jual' => 0,
                ]);
    
            // Hapus Faktur
            $faktur->delete();
    
            return redirect()->back()->with('success', 'Faktur dan data terkait berhasil dihapus');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function uploadBukti(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:t_faktur_online,id',
            'bukti_tf' => 'required|image|mimes:jpeg,png,jpg|max:10240'
        ]);

        $faktur = FakturOnline::findOrFail($request->id);

        // Simpan gambar di folder 'bukti_transfer'
        if ($request->hasFile('bukti_tf')) {
            $file = $request->file('bukti_tf');
            $filePath = $file->store('bukti_transfer_online', 'public');

            // Hapus bukti lama jika ada
            if ($faktur->bukti_tf) {
                $oldFilePath = str_replace('/storage/', '', $faktur->bukti_tf);
                Storage::disk('public')->delete($oldFilePath);
            }

            // Simpan path bukti transfer di database
            $faktur->bukti_tf = "/storage/" . $filePath;
            $faktur->save();
        }

        return redirect()->back()->with('success', 'Bukti transfer berhasil diupload.');
    }

    public function tandaiSudahDicek($id)
    {
        try {
            // Cari faktur berdasarkan nomor_faktur
            $faktur = FakturOnline::where('id', $id)->firstOrFail();

            $faktur->is_finish = 1;
            $faktur->save();
    
            return redirect()->back()->with('success', 'Faktur ditandai sudah selesai');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
}

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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class KirimBarangController extends Controller
{
    public function index()
    {
        $kirims = Kirim::orderBy('status')
            ->orderBy('dt_kirim', 'desc')
            ->get();

        // Ambil semua gudang
        $allgudangs = Gudang::all();

        // Hitung jumlah barang berdasarkan kirim_id
        $jumlahBarang = KirimBarang::selectRaw('kirim_id, COUNT(*) as jumlah')
            ->groupBy('kirim_id')
            ->pluck('jumlah', 'kirim_id');

        return view('pages.kirim-barang.index', compact('kirims', 'allgudangs', 'jumlahBarang')); 
    }

    public function store(Request $request)
    {
        $request->validate([
            'filedata' => 'required|file|mimes:xlsx,xls',
            'penerima_gudang_id' => 'required',
        ]);

        $errors = [];
        $validatedLokSpk = [];
        $file = $request->file('filedata');
        $data = Excel::toArray([], $file);

        $pengirimGudangId = Auth::user()->gudang_id;

        if (!$pengirimGudangId) {
            return redirect()->back()->with('errors', ['User pengirim tidak terasosiasi dengan gudang manapun.']);
        }
        
        foreach ($data[0] as $index => $row) {
            if ($index === 0) continue;
            
            $rowNumber = $index + 1;
            $lokSpk = $row[0] ?? null;

            if (empty($lokSpk)) {
                $errors[] = "Baris $rowNumber: Kolom Lok SPK tidak boleh kosong.";
                continue;
            }

            $barang = Barang::where('lok_spk', $lokSpk)->first();

            if ($barang) {
                if ($barang->status_barang != 1) {
                    $errors[] = "Baris $rowNumber: Status barang untuk LOK SPK '$lokSpk' tidak sesuai (bukan status 'Tersedia').";
                }
                
                if ($barang->gudang_id != $pengirimGudangId) {
                    $errors[] = "Baris $rowNumber: Barang dengan LOK SPK '$lokSpk' tidak berada di gudang pengirim.";
                }

                if (empty($errors)) {
                     $validatedLokSpk[] = $lokSpk;
                }

            } else {
                $errors[] = "Baris $rowNumber: LOK SPK '$lokSpk' tidak ditemukan di database.";
            }
        }

        if (!empty($errors)) {
            return redirect()->back()->with('errors', $errors);
        }

        if (empty($validatedLokSpk)) {
            return redirect()->back()->with('errors', ['Tidak ada data valid yang ditemukan di dalam file.']);
        }

        try {
            DB::transaction(function () use ($request, $validatedLokSpk, $pengirimGudangId) {
                $penerimaGudangId = $request->input('penerima_gudang_id');
                $gudangPenerima = Gudang::find($penerimaGudangId);

                $kirim = Kirim::create([
                    'pengirim_gudang_id' => $pengirimGudangId,
                    'penerima_gudang_id' => $penerimaGudangId,
                    'pengirim_user_id' => Auth::id(),
                    'penerima_user_id' => $gudangPenerima->pj_gudang,
                    'status' => 0,
                    'keterangan' => $request->input('keterangan'),
                    'dt_kirim' => Carbon::now(),
                ]);

                foreach ($validatedLokSpk as $lokSpk) {
                    KirimBarang::create([
                        'lok_spk' => $lokSpk,
                        'kirim_id' => $kirim->id,
                    ]);
                }
            });

        } catch (\Exception $e) {
            return redirect()->back()->with('errors', ['Terjadi kesalahan pada server saat menyimpan data: ' . $e->getMessage()]);
        }
        
        return redirect()->back()
            ->with('success', 'Data berhasil disimpan. ' . count($validatedLokSpk) . ' barang telah diproses untuk dikirim.');
    }

    public function destroy($id)
    {
        try {
            $kirim = Kirim::where('id', $id)->firstOrFail();
            $kirim->delete();

            return redirect()->back()->with('success', 'Kirim berhasil dihapus');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function destroybarang($id)
    {
        try {
            $kirim = KirimBarang::where('id', $id)->firstOrFail();
            $kirim->delete();

            return redirect()->back()->with('success', 'Barang berhasil dihapus');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
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

        // Hitung jumlah barang
        $jumlahBarang = $kirimBarangs->count();

        return view('pages.kirim-barang.detail', compact('kirim', 'kirimBarangs', 'jumlahBarang'));
    }  

    public function printPdf($id)
    {
        // Ambil data faktur berdasarkan nomor faktur
        $kirim = Kirim::where('id', $id)
            ->firstOrFail();

        // Ambil data barang yang berhubungan dengan transaksi jual
        $kirimBarangs = KirimBarang::with('barang')
            ->where('kirim_id', $id)
            ->get();

        // Hitung jumlah barang
        $jumlahBarang = $kirimBarangs->count();

        // Kirim data ke template PDF
        $pdf = \PDF::loadView('pages.kirim-barang.print', compact('kirim', 'kirimBarangs', 'jumlahBarang'));

        // Unduh atau tampilkan PDF
        return $pdf->stream('Detail_Kirim_Barang_' . $kirim->id . '.pdf');
    }

    public function addbarang(Request $request)
    {
        $request->validate([
            'filedata' => 'required|file|mimes:xlsx,xls',
            'kirim_id' => 'required',
        ]);
    
        // Inisialisasi variabel
        $errors = [];
        $validLokSpk = [];
    
        // Membaca file Excel
        $file = $request->file('filedata');
        $data = Excel::toArray([], $file);
    
        foreach ($data[0] as $index => $row) {
            // Lewati baris pertama jika merupakan header
            if ($index === 0) continue;
    
            // Validasi kolom di Excel
            if (isset($row[0])) {
                $lokSpk = $row[0]; // Lok SPK
    
                // Cari barang berdasarkan lok_spk
                $barang = Barang::where('lok_spk', $lokSpk)->first();
    
                if ($barang) {
                    // Cek apakah status_barang adalah 0 atau 1
                    if (in_array($barang->status_barang, [1])) {
                        // Simpan lok_spk untuk update nanti
                        $validLokSpk[] = [
                            'lok_spk' => $lokSpk,
                        ];
                    } else {
                        $errors[] = "Row " . ($index + 1) . ": Lok SPK '$lokSpk' memiliki status_barang yang tidak sesuai.";
                    }
                } else {
                    $errors[] = "Row " . ($index + 1) . ": Lok SPK '$lokSpk' tidak ditemukan.";
                }
            } else {
                $errors[] = "Row " . ($index + 1) . ": Data tidak valid (Lok SPK kosong).";
            }
        }
    
        // Simpan data Faktur jika ada data valid
        if (!empty($validLokSpk)) {
            // Update Barang untuk lok_spk yang valid
            foreach ($validLokSpk as $item) {

                KirimBarang::create([
                    'lok_spk' => $item['lok_spk'],
                    'kirim_id' => $request->input('kirim_id'),
                ]);
            }
    
            // Tampilkan pesan sukses dan error
            return redirect()->back()
                ->with('success', 'Barang berhasil ditambah. ' . count($validLokSpk) . ' barang diproses.')
                ->with('errors', $errors);
        }
    
        // Jika tidak ada data valid, hanya tampilkan error
        return redirect()->back()
            ->with('errors', $errors);
    }

    public function printBukti($id)
    {
        // Ambil data faktur berdasarkan nomor faktur
        $kirim = Kirim::where('id', $id)
            ->firstOrFail();

        // Ambil data barang yang berhubungan dengan transaksi jual
        $kirimBarangs = KirimBarang::with('barang')
            ->where('kirim_id', $id)
            ->get();

        // Hitung jumlah barang
        $jumlahBarang = $kirimBarangs->count();

        // Kirim data ke template PDF
        $pdf = \PDF::loadView('pages.kirim-barang.print-bukti', compact('kirim', 'kirimBarangs', 'jumlahBarang'));

        // Unduh atau tampilkan PDF
        return $pdf->stream('Bukti_Kirim_Barang_' . $kirim->id . '.pdf');
    }

    public function uploadBukti(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:t_kirim,id',
            'bukti_tf' => 'required|image|mimes:jpeg,png,jpg|max:10240'
        ]);

        $kirim = Kirim::findOrFail($request->id);

        // Simpan gambar di folder 'bukti_transfer'
        if ($request->hasFile('bukti_tf')) {
            $file = $request->file('bukti_tf');
            $filePath = $file->store('bukti_kirim_barang', 'public');

            // Hapus bukti lama jika ada
            if ($kirim->bukti_tf) {
                $oldFilePath = str_replace('/storage/', '', $kirim->bukti_tf);
                Storage::disk('public')->delete($oldFilePath);
            }

            // Simpan path bukti transfer di database
            $kirim->bukti_tf = "/storage/" . $filePath;
            $kirim->save();
        }

        return redirect()->back()->with('success', 'Bukti kirim barang berhasil diupload.');
    }
}

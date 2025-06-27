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
use App\Exports\FakturBawahExport;

class TransaksiFakturBawahController extends Controller
{
    public function index(Request $request)
    {
        $query = FakturBawah::with(['fakturKesimpulan.kesimpulan']) 
            ->withCount(['barangs as total_barang'])
            ->orderBy('tgl_jual', 'desc');
    
        $roleUser = optional(Auth::user())->role;
        $gudangId = optional(Auth::user())->gudang_id;
    
        // Filter berdasarkan rentang tanggal
        if ($request->filled('tanggal_mulai') && $request->filled('tanggal_selesai')) {
            $query->whereBetween('tgl_jual', [$request->tanggal_mulai, $request->tanggal_selesai]);
        }

        if ($request->filled('cek')) {
            $query->where('is_finish', $request->cek == 'Sudah_Dicek' ? 1 : 0);
        }

        if ($request->filled('status_kesimpulan')) {
            if ($request->status_kesimpulan == 'ada') {
                // Hanya ambil faktur yang memiliki relasi ke faktur_kesimpulan
                $query->whereHas('fakturKesimpulan');
            } elseif ($request->status_kesimpulan == 'tidak_ada') {
                // Hanya ambil faktur yang TIDAK memiliki relasi ke faktur_kesimpulan
                $query->whereDoesntHave('fakturKesimpulan');
            }
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

        $errors = [];
        $totalHargaJual = $request->input('total');
        $validLokSpk = [];
        $processedLokSpk = [];

        $file = $request->file('filedata');
        $data = Excel::toArray([], $file);

        // Ambil data faktur terkait
        $faktur = FakturBawah::where('nomor_faktur', $request->input('nomor_faktur'))->first();

        if (!$faktur) {
            return redirect()->back()->with('error', 'Faktur tidak ditemukan.');
        }

        $grade = $faktur->grade;
        $tgl_jual = $faktur->tgl_jual;

        foreach ($data[0] as $index => $row) {
            if ($index === 0) continue;

            if (isset($row[0]) && isset($row[1])) {
                $lokSpk = $row[0];
                $hargaJual = $row[1] * 1000;

                if (in_array($lokSpk, $processedLokSpk)) {
                    $errors[] = "Row " . ($index + 1) . ": Lok SPK '$lokSpk' duplikat di dalam file Excel.";
                    continue;
                }

                $processedLokSpk[] = $lokSpk;

                $existsInDatabase = TransaksiJualBawah::where('lok_spk', $lokSpk)
                    ->where('nomor_faktur', $request->input('nomor_faktur'))
                    ->exists();

                if ($existsInDatabase) {
                    $errors[] = "Row " . ($index + 1) . ": Lok SPK '$lokSpk' dengan Nomor FakturBawah '{$request->input('nomor_faktur')}' sudah ada di database.";
                    continue;
                }

                $barang = Barang::where('lok_spk', $lokSpk)->first();

                if ($barang) {
                    if (in_array($barang->status_barang, [0, 1])) {
                        $tipe = $barang->tipe;

                        // Validasi harga berdasarkan tipe + grade + tgl_jual
                        $existingTransaksi = TransaksiJualBawah::whereHas('barang', function ($query) use ($tipe) {
                            $query->where('tipe', $tipe);
                        })
                        ->whereHas('barang.fakturBawah', function ($query) use ($grade, $tgl_jual) {
                            $query->where('grade', $grade)->where('tgl_jual', $tgl_jual);
                        })
                        ->first();

                        if ($existingTransaksi && $existingTransaksi->harga != $hargaJual) {
                            $errors[] = "Row " . ($index + 1) . ": Lok SPK '$lokSpk' memiliki harga jual ($hargaJual) berbeda dari transaksi sebelumnya untuk tipe '$tipe' dan grade '$grade' di tanggal $tgl_jual (harga sebelumnya: $existingTransaksi->harga). ";
                            continue;
                        }

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

        if (!empty($validLokSpk)) {
            FakturBawah::where('nomor_faktur', $request->input('nomor_faktur'))
                ->update([
                    'total' => $totalHargaJual,
                ]);

            foreach ($validLokSpk as $item) {
                $tipe = Barang::where('lok_spk', $item['lok_spk'])->pluck('tipe')->first();

                $negoan = Negoan::where('tipe', $tipe)
                    ->where('grade', $grade)
                    ->where('status', 1)
                    ->orderBy('updated_at', 'desc')
                    ->first();

                Barang::where('lok_spk', $item['lok_spk'])->update([
                    'no_faktur' => $request->input('nomor_faktur'),
                    'harga_jual' => $item['harga_jual'],
                ]);

                TransaksiJualBawah::create([
                    'lok_spk' => $item['lok_spk'],
                    'nomor_faktur' => $request->input('nomor_faktur'),
                    'harga' => $item['harga_jual'],
                    'harga_acc' => $negoan->harga_acc ?? 0,
                ]);
            }

            return redirect()->back()
                ->with('success', 'FakturBawah berhasil disimpan. ' . count($validLokSpk) . ' barang diproses.')
                ->with('errors', $errors);
        }

        return redirect()->back()->with('errors', $errors);
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
            // Ambil faktur beserta transaksi jual dan barang-nya
            $faktur = FakturBawah::with('transaksiJuals.barang')->where('id', $id)->firstOrFail();

            // Update is_finish
            $faktur->is_finish = 1;
            $faktur->save();

            // Loop semua transaksi jual
            foreach ($faktur->transaksiJuals as $transaksi) {
                if ($transaksi->barang) {
                    $transaksi->barang->status_barang = 2;
                    $transaksi->barang->save();
                }
            }

            return redirect()->back()->with('success', 'Faktur ditandai sudah selesai dan barang diperbarui.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function printMultiple(Request $request)
    {
        $query = FakturBawah::with(['barangs', 'transaksiJuals.barang'])->orderBy('tgl_jual', 'desc');

        if ($request->filled('tanggal_mulai') && $request->filled('tanggal_selesai')) {
            $query->whereBetween('tgl_jual', [$request->tanggal_mulai, $request->tanggal_selesai]);
        }

        if ($request->filled('cek')) {
            $query->where('is_finish', $request->cek == 'Sudah_Dicek' ? 1 : 0);
        }

        if ($request->filled('status_kesimpulan')) {
            if ($request->status_kesimpulan == 'ada') {
                $query->whereHas('fakturKesimpulan');
            } elseif ($request->status_kesimpulan == 'tidak_ada') {
                $query->whereDoesntHave('fakturKesimpulan');
            }
        }

        $fakturs = $query->get();

        // Ambil transaksi jual masing-masing faktur
        foreach ($fakturs as $faktur) {
            $faktur->transaksiJuals = TransaksiJualBawah::with('barang')
                ->where('nomor_faktur', $faktur->nomor_faktur)
                ->get();

            $subtotalKumulatif = 0;
            $faktur->transaksiJuals->map(function ($transaksi) use (&$subtotalKumulatif) {
                $subtotalKumulatif += $transaksi->harga;
                $transaksi->subtotal = $subtotalKumulatif;
                return $transaksi;
            });

            $faktur->totalHarga = $faktur->transaksiJuals->sum('harga');
        }

        $pdf = \PDF::loadView('pages.transaksi-faktur-bawah.print-multiple', compact('fakturs'))
            ->setPaper('A4', 'portrait');

        return $pdf->stream('Daftar_Faktur.pdf');
    }

    public function exportMultiple(Request $request)
    {
        // Mulai query untuk mendapatkan faktur dan relasi yang dibutuhkan
        $query = FakturBawah::with(['barangs', 'transaksiJuals.barang'])->orderBy('tgl_jual', 'desc');

        // Filter berdasarkan tanggal jika ada
        if ($request->filled('tanggal_mulai') && $request->filled('tanggal_selesai')) {
            $query->whereBetween('tgl_jual', [$request->tanggal_mulai, $request->tanggal_selesai]);
        }

        if ($request->filled('cek')) {
            $query->where('is_finish', $request->cek == 'Sudah_Dicek' ? 1 : 0);
        }

        // Ambil data faktur sesuai query
        $fakturs = $query->get();

        // Ambil transaksi jual dan hitung subtotal
        foreach ($fakturs as $faktur) {
            $faktur->transaksiJuals = TransaksiJualBAwah::with('barang')
                ->where('nomor_faktur', $faktur->nomor_faktur)
                ->get();

            $subtotalKumulatif = 0;
            $faktur->transaksiJuals->map(function ($transaksi) use (&$subtotalKumulatif) {
                $subtotalKumulatif += $transaksi->harga;
                $transaksi->subtotal = $subtotalKumulatif;
                return $transaksi;
            });

            $faktur->totalHarga = $faktur->transaksiJuals->sum('harga');
        }

        // Ekspor ke Excel
        return Excel::download(new FakturBawahExport($fakturs), 'faktur_bawah.xlsx');
    }

    public function printKesimpulan(Request $request)
    {
        // 1. Ambil query dasar yang sama persis dengan method index()
        $query = FakturBawah::withCount(['barangs as total_barang'])
            ->orderBy('tgl_jual', 'asc'); // Kita urutkan dari yang terlama untuk subtotal

        // 2. Terapkan filter yang sama persis dengan method index()
        if ($request->filled('tanggal_mulai') && $request->filled('tanggal_selesai')) {
            $query->whereBetween('tgl_jual', [$request->tanggal_mulai, $request->tanggal_selesai]);
        }

        if ($request->filled('cek')) {
            $query->where('is_finish', $request->cek == 'Sudah_Dicek' ? 1 : 0);
        }

        if ($request->filled('status_kesimpulan')) {
            if ($request->status_kesimpulan == 'ada') {
                $query->whereHas('fakturKesimpulan');
            } elseif ($request->status_kesimpulan == 'tidak_ada') {
                $query->whereDoesntHave('fakturKesimpulan');
            }
        }
        
        // 3. Eksekusi query untuk mendapatkan koleksi data faktur
        $fakturs = $query->get();

        // 4. Hitung data untuk header info PDF
        $totalJumlahBarang = $fakturs->sum('total_barang');
        $totalHargaKeseluruhan = $fakturs->sum('total');

        $tanggalMulai = $fakturs->isNotEmpty() ? Carbon::parse($fakturs->first()->tgl_jual)->translatedFormat('d M Y') : 'N/A';
        $tanggalSelesai = $fakturs->isNotEmpty() ? Carbon::parse($fakturs->last()->tgl_jual)->translatedFormat('d M Y') : 'N/A';
        
        $rentangTanggal = ($tanggalMulai == $tanggalSelesai) ? $tanggalMulai : $tanggalMulai . ' - ' . $tanggalSelesai;

        // 5. Load view PDF dan kirimkan data yang dibutuhkan
        $pdf = \PDF::loadView('pages.transaksi-faktur-bawah.print-kesimpulan', compact(
            'fakturs', 
            'totalJumlahBarang', 
            'totalHargaKeseluruhan', 
            'rentangTanggal'
        ));
        
        // 6. Tampilkan PDF di browser
        return $pdf->stream('kesimpulan-faktur-' . time() . '.pdf');
    }
}

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
use App\Models\HistoryEditFakturBawah;
use Illuminate\Support\Facades\DB;
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
        $validated = $request->validate([
            'pembeli' => 'required|string|max:255',
            'tgl_jual' => 'required|date',
            'petugas' => 'required|string|max:255',
            'grade' => 'required',
            'keterangan' => 'nullable|string',
        ]);

        try {
            DB::transaction(function () use ($validated, $id, $request) {
                $faktur = FakturBawah::findOrFail($id);
                $perubahan = [];

                if ($faktur->pembeli !== $validated['pembeli']) {
                    $perubahan[] = "Pembeli diubah dari '{$faktur->pembeli}' menjadi '{$validated['pembeli']}'";
                }
                if (Carbon::parse($faktur->tgl_jual)->notEqualTo(Carbon::parse($validated['tgl_jual']))) {
                    $perubahan[] = "Tgl Jual diubah dari '" . Carbon::parse($faktur->tgl_jual)->format('d-m-Y') . "' menjadi '" . Carbon::parse($validated['tgl_jual'])->format('d-m-Y') . "'";
                }
                if ($faktur->petugas !== $validated['petugas']) {
                    $perubahan[] = "Petugas diubah dari '{$faktur->petugas}' menjadi '{$validated['petugas']}'";
                }
                if ($faktur->grade !== $validated['grade']) {
                    $perubahan[] = "Grade diubah dari '{$faktur->grade}' menjadi '{$validated['grade']}'";
                }
                if ($faktur->keterangan !== $validated['keterangan']) {
                    $perubahan[] = "Keterangan diubah.";
                }

                if (!empty($perubahan)) {
                    HistoryEditFakturBawah::create([
                        'faktur_id' => $faktur->id,
                        'update'    => implode('<br>', $perubahan),
                        'user_id'   => auth()->id(),
                    ]);
                }

                $faktur->update($validated);
            });

            session()->flash('success', 'Faktur berhasil diupdate');
            return redirect()->route('transaksi-faktur-bawah.index');

        } catch (\Exception $e) {
            session()->flash('error', 'Terjadi kesalahan: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    public function destroy($nomor_faktur)
    {
        try {
            DB::transaction(function () use ($nomor_faktur) {
                $faktur = FakturBawah::where('nomor_faktur', $nomor_faktur)->firstOrFail();
                $lokSpkList = TransaksiJualBawah::where('nomor_faktur', $nomor_faktur)->pluck('lok_spk');

                HistoryEditFakturBawah::create([
                    'faktur_id' => $faktur->id,
                    'update'    => "Menghapus faktur dengan nomor: {$faktur->nomor_faktur}",
                    'user_id'   => auth()->id(),
                ]);

                TransaksiJualBawah::where('nomor_faktur', $nomor_faktur)->delete();

                if ($lokSpkList->isNotEmpty()) {
                    Barang::whereIn('lok_spk', $lokSpkList)->update([
                        'status_barang' => 1,
                        'no_faktur' => null,
                        'harga_jual' => 0,
                    ]);
                }
                
                $faktur->delete();
            });

            return redirect()->back()->with('success', 'Faktur dan data terkait berhasil dihapus');
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

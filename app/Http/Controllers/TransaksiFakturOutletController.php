<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\Barang;
use App\Models\FakturBuktiOutlet;
use App\Models\FakturOutlet;
use App\Models\TransaksiJualOutlet;
use App\Models\HistoryEditFakturOutlet;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TransaksiFakturOutletController extends Controller
{
    public function index(Request $request)
    {
        // PERUBAHAN 1: Menambahkan withSum untuk relasi 'payments'
        $query = FakturOutlet::withCount(['barangs as total_barang'])
            ->withSum('bukti as total_nominal_bukti', 'nominal') // Total dari bukti manual
            ->withSum(['payments as total_payment_online' => function($q) { // Total dari payment online
                $q->whereIn('status', ['settlement', 'capture']);
            }], 'amount')
            ->orderBy('tgl_jual', 'desc');
    
        $roleUser = optional(Auth::user())->role;
        $gudangId = optional(Auth::user())->gudang_id;

        if($roleUser == 'admin'){
            $daftarGudang = ['O-JK', 'O-AD', 'O-PY'];
        
            if ($request->filled('kode_faktur')) {
                $kodeFaktur = $request->kode_faktur;
        
                if (in_array($kodeFaktur, $daftarGudang)) {
                    $query->where('nomor_faktur', 'like', "$kodeFaktur-%");
                } else {
                    $query->where(function ($q) use ($daftarGudang) {
                        foreach ($daftarGudang as $kode) {
                            $q->where('nomor_faktur', 'not like', "$kode-%");
                        }
                    });
                }
            }
        }else{
            switch ($gudangId) {
                case 8:
                    $query->where('nomor_faktur', 'like', "O-JK-%");
                    break;
                case 9:
                    $query->where('nomor_faktur', 'like', "O-AD-%");
                    break;
                case 10:
                    $query->where('nomor_faktur', 'like', "O-PY-%");
                    break;
                default:
                    break;
            }           
        }
    
        if ($request->filled('tanggal_mulai') && $request->filled('tanggal_selesai')) {
            $query->whereBetween('tgl_jual', [$request->tanggal_mulai, $request->tanggal_selesai]);
        }
    
        if ($request->filled('status')) {
            $query->where('is_lunas', $request->status == 'Lunas' ? 1 : 0);
        }

        if ($request->filled('cek')) {
            $query->where('is_finish', $request->cek == 'Sudah_Dicek' ? 1 : 0);
        }
    
        $fakturs = $query->get();
    
        // PERUBAHAN 2: Logika kalkulasi disederhanakan
        foreach ($fakturs as $faktur) {
            $totalNominal = ($faktur->total_nominal_bukti ?? 0) + ($faktur->total_payment_online ?? 0);
            $newIsLunas = ($totalNominal >= $faktur->total) ? 1 : 0;

            if ($faktur->is_lunas !== $newIsLunas) {
                $faktur->is_lunas = $newIsLunas;
                $faktur->save();
            }
            
            // Tambahkan atribut total_nominal untuk ditampilkan di view
            $faktur->total_nominal = $totalNominal;
        }
    
        return view('pages.transaksi-faktur-outlet.index', compact('fakturs', 'roleUser'));
    }    

    public function show($nomor_faktur)
    {
        // PERUBAHAN 3: Menambahkan relasi 'payments' dan kalkulasi total
        $faktur = FakturOutlet::with(['barangs', 'bukti', 'payments'])
            ->where('nomor_faktur', $nomor_faktur)
            ->firstOrFail();

        $totalBuktiManual = $faktur->bukti->sum('nominal');
        $totalPaymentOnline = $faktur->payments->whereIn('status', ['settlement', 'capture'])->sum('amount');
        $totalNominal = $totalBuktiManual + $totalPaymentOnline;

        // Logika untuk update is_lunas jika statusnya tidak sesuai
        $newIsLunas = ($totalNominal >= $faktur->total) ? 1 : 0;
        if (property_exists($faktur, 'is_lunas') && $faktur->is_lunas !== $newIsLunas) {
            $faktur->is_lunas = $newIsLunas;
            $faktur->save();
        }

        $transaksiJuals = TransaksiJualOutlet::with('barang')
            ->where('nomor_faktur', $nomor_faktur)
            ->get();

        $roleUser = optional(Auth::user())->role;

        return view('pages.transaksi-faktur-outlet.detail', compact('faktur', 'transaksiJuals', 'roleUser', 'totalNominal'));
    }

    public function printPdf($nomor_faktur)
    {
        $faktur = FakturOutlet::with('barangs')
            ->where('nomor_faktur', $nomor_faktur)
            ->firstOrFail();
        $transaksiJuals = TransaksiJualOutlet::with('barang')
            ->where('nomor_faktur', $nomor_faktur)
            ->get();
        $subtotalKumulatif = 0;
        $transaksiJuals->map(function ($transaksi) use (&$subtotalKumulatif) {
            $subtotalKumulatif += $transaksi->harga;
            $transaksi->subtotal = $subtotalKumulatif;
            return $transaksi;
        });
        $totalHarga = $transaksiJuals->sum('harga');
        $pdf = \PDF::loadView('pages.transaksi-faktur-outlet.print', compact('faktur', 'transaksiJuals', 'totalHarga'));
        return $pdf->stream('Faktur_Penjualan_' . $faktur->nomor_faktur . '.pdf');
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'nomor_faktur' => 'required|string|max:255|unique:t_faktur_outlet,nomor_faktur,' . $id,
            'pembeli' => 'required|string|max:255',
            'tgl_jual' => 'required|date',
            'petugas' => 'required|string|max:255',
            'grade' => 'required',
            'keterangan' => 'nullable|string',
        ]);
        try {
            DB::transaction(function () use ($validated, $id) {
                $faktur = FakturOutlet::findOrFail($id);
                $nomorFakturLama = $faktur->nomor_faktur;
                $perubahan = [];
                if ($faktur->nomor_faktur !== $validated['nomor_faktur']) $perubahan[] = "No Faktur diubah dari '{$faktur->nomor_faktur}' menjadi '{$validated['nomor_faktur']}'";
                if ($faktur->pembeli !== $validated['pembeli']) $perubahan[] = "Pembeli diubah dari '{$faktur->pembeli}' menjadi '{$validated['pembeli']}'";
                if (!empty($perubahan)) {
                    HistoryEditFakturOutlet::create(['faktur_id' => $faktur->id, 'update' => implode('<br>', $perubahan), 'user_id' => auth()->id()]);
                }
                $faktur->update($validated);
                if ($nomorFakturLama !== $validated['nomor_faktur']) {
                    TransaksiJualOutlet::where('nomor_faktur', $nomorFakturLama)->update(['nomor_faktur' => $validated['nomor_faktur']]);
                }
            });
            return redirect()->route('transaksi-faktur-outlet.index')->with('success', 'Faktur berhasil diupdate');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    public function destroy($nomor_faktur)
    {
        try {
            DB::transaction(function () use ($nomor_faktur) {
                $faktur = FakturOutlet::where('nomor_faktur', $nomor_faktur)->firstOrFail();
                HistoryEditFakturOutlet::create(['faktur_id' => $faktur->id, 'update' => "Menghapus faktur: {$faktur->nomor_faktur}", 'user_id' => auth()->id()]);
                $lokSpkList = TransaksiJualOutlet::where('nomor_faktur', $nomor_faktur)->pluck('lok_spk');
                TransaksiJualOutlet::where('nomor_faktur', $nomor_faktur)->delete();
                if ($lokSpkList->isNotEmpty()) {
                    Barang::whereIn('lok_spk', $lokSpkList)->update(['status_barang' => 1, 'no_faktur' => null, 'harga_jual' => 0]);
                }
                $faktur->payments()->delete(); // Hapus juga payment online terkait
                $faktur->delete();
            });
            return redirect()->back()->with('success', 'Faktur dan data terkait berhasil dihapus');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    public function storeBukti(Request $request)
    {
        $request->validate([
            't_faktur_id' => 'required|exists:t_faktur_outlet,id',
            'keterangan' => 'string|max:255',
            'nominal' => 'required|numeric',
            'foto' => 'required|image'
        ]);
    
        $path = $request->file('foto')->store('faktur_bukti', 'public');
    
        FakturBuktiOutlet::create([
            't_faktur_id' => $request->t_faktur_id,
            'keterangan' => $request->keterangan,
            'nominal' => $request->nominal,
            'foto' => $path
        ]);
    
        // PERUBAHAN 4: Logika update status lunas setelah tambah bukti
        $this->updateLunasStatus($request->t_faktur_id);
    
        return back()->with('success', 'Bukti transfer berhasil ditambahkan.');
    }    

    public function deleteBukti($id)
    {
        $bukti = FakturBuktiOutlet::findOrFail($id);
        Storage::disk('public')->delete($bukti->foto);
        
        $tFakturId = $bukti->t_faktur_id;
        $bukti->delete();

        // PERUBAHAN 5: Logika update status lunas setelah hapus bukti
        $this->updateLunasStatus($tFakturId);

        return back()->with('success', 'Bukti transfer berhasil dihapus.');
    }

    // PERUBAHAN 6: Helper method untuk update status lunas
    private function updateLunasStatus($fakturId)
    {
        $faktur = FakturOutlet::with('payments')->find($fakturId);
        if (!$faktur) return;

        $totalBuktiManual = $faktur->bukti()->sum('nominal');
        $totalPaymentOnline = $faktur->payments->whereIn('status', ['settlement', 'capture'])->sum('amount');
        $totalPaid = $totalBuktiManual + $totalPaymentOnline;
        
        $newIsLunas = ($totalPaid >= $faktur->total) ? 1 : 0;

        if ($faktur->is_lunas !== $newIsLunas) {
            $faktur->is_lunas = $newIsLunas;
            $faktur->save();
        }
    }

    public function tandaiSudahDicek($id)
    {
        try {
            $faktur = FakturOutlet::with('transaksiJuals.barang')->where('id', $id)->firstOrFail();
            $faktur->is_finish = 1;
            $faktur->save();
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

    public function tandaiBelumDicek($id)
    {
        try {
            // Ambil faktur beserta transaksi jual dan barang-nya
            $faktur = FakturOutlet::with('transaksiJuals.barang')->where('id', $id)->firstOrFail();

            // Update is_finish
            $faktur->is_finish = 0;
            $faktur->save();

            // Loop semua transaksi jual
            foreach ($faktur->transaksiJuals as $transaksi) {
                if ($transaksi->barang) {
                    $transaksi->barang->status_barang = 5;
                    $transaksi->barang->save();
                }
            }

            return redirect()->back()->with('success', 'Faktur ditandai belum dicek dan barang diperbarui.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function rekap(Request $request)
    {
        // Definisi nama gudang
        $daftarGudang = [
            'AT' => 'Gudang Zilfa',
            'TKP' => 'Gudang Tokopedia',
            'VR' => 'Gudang Vira',
            'BW' => 'Gudang Bawah',
            'LN' => 'Gudang Lain Lain'
        ];
    
        // Ambil filter dari request
        $filterGudang = $request->input('gudang');
        $filterBulan = $request->input('bulan');
    
        // Subquery untuk menghitung total barang terjual per faktur
        $subquery = TransaksiJualOutlet::selectRaw("nomor_faktur, COUNT(*) as total_barang")
            ->groupBy('nomor_faktur');
    
        // Query utama
        $query = FakturOutlet::selectRaw("
                LEFT(t_faktur_outlet.nomor_faktur, LOCATE('-', t_faktur_outlet.nomor_faktur) - 1) as kode_gudang, 
                DATE_FORMAT(t_faktur_outlet.tgl_jual, '%m-%Y') as bulan_sort, 
                DATE_FORMAT(t_faktur_outlet.tgl_jual, '%Y-%m') as bulan_display, 
                SUM(t_faktur_outlet.total) as total_pendapatan, 
                COALESCE(SUM(sub.total_barang), 0) as total_barang
            ")
            ->leftJoinSub($subquery, 'sub', function ($join) {
                $join->on('t_faktur_outlet.nomor_faktur', '=', 'sub.nomor_faktur');
            })
            ->groupBy('kode_gudang', 'bulan_sort', 'bulan_display');
    
        // Terapkan filter jika ada input
        if (!empty($filterGudang)) {
            $query->having('kode_gudang', '=', $filterGudang);
        }
        if (!empty($filterBulan)) {
            $query->having('bulan_display', '=', $filterBulan);
        }        
    
        $data = $query->orderBy('bulan_sort', 'desc')->get();
    
        // Mapping data dengan nama bulan dalam bahasa Indonesia
        $rekaps = $data->map(function ($item) use ($daftarGudang) {
            return (object) [
                'nama_gudang' => $daftarGudang[$item->kode_gudang] ?? 'Tidak Diketahui',
                'bulan' => Carbon::createFromFormat('Y-m', $item->bulan_display)->translatedFormat('F Y'), 
                'total_pendapatan' => $item->total_pendapatan,
                'total_barang' => $item->total_barang
            ];
        });
    
        return view('pages.transaksi-faktur-outlet.rekap', compact('rekaps', 'daftarGudang', 'filterGudang', 'filterBulan'));
    }    
}

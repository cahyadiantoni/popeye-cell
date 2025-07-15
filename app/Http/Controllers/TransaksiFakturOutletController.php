<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\FakturBuktiOutlet;
use App\Models\Gudang;
use App\Models\Negoan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Kirim;
use App\Models\FakturOutlet;
use App\Models\TransaksiJualOutlet;
use App\Models\HistoryEditFakturOutlet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

class TransaksiFakturOutletController extends Controller
{
    public function index(Request $request)
    {
        $query = FakturOutlet::withCount(['barangs as total_barang'])
            ->withSum('bukti as total_nominal', 'nominal') // Assuming the relationship is defined
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
    
        // Filter berdasarkan rentang tanggal
        if ($request->filled('tanggal_mulai') && $request->filled('tanggal_selesai')) {
            $query->whereBetween('tgl_jual', [$request->tanggal_mulai, $request->tanggal_selesai]);
        }
    
        // Filter berdasarkan status Lunas/Hutang
        if ($request->filled('status')) {
            if ($request->status == 'Lunas') {
                $query->where('is_lunas', 1);
            } elseif ($request->status == 'Hutang') {
                $query->where('is_lunas', 0);
            }
        }

        if ($request->filled('cek')) {
            $query->where('is_finish', $request->cek == 'Sudah_Dicek' ? 1 : 0);
        }
    
        $fakturs = $query->get();
    
        // Loop through each FakturOutlet record to update is_lunas
        foreach ($fakturs as $faktur) {
            // Set total_nominal to 0 if it is empty
            if (empty($faktur->total_nominal)) {
                $total_nominal = 0; 
            } else {
                $total_nominal = $faktur->total_nominal;
            }
    
            // Update is_lunas based on the comparison
            if ($total_nominal >= $faktur->total) {
                $faktur->is_lunas = 1; // Update is_lunas to 1
                $faktur->update(); // Update is_lunas to 1
            } else {
                $faktur->is_lunas = 0; // Update is_lunas to 0
                $faktur->update(); // Update is_lunas to 0
            }
        }
    
        return view('pages.transaksi-faktur-outlet.index', compact('fakturs', 'roleUser'));
    }    

    public function show($nomor_faktur)
    {
        // Ambil data faktur berdasarkan nomor faktur
        $faktur = FakturOutlet::with('barangs', 'bukti')
            ->where('nomor_faktur', $nomor_faktur)
            ->firstOrFail();

        // Calculate the total nominal from the bukti relationship
        $totalNominal = $faktur->bukti->sum('nominal');

        // Ambil data barang yang berhubungan dengan transaksi jual
        $transaksiJuals = TransaksiJualOutlet::with('barang')
            ->where('nomor_faktur', $nomor_faktur)
            ->get();

        $roleUser = optional(Auth::user())->role;

        return view('pages.transaksi-faktur-outlet.detail', compact('faktur', 'transaksiJuals', 'roleUser', 'totalNominal'));
    }

    public function printPdf($nomor_faktur)
    {
        // Ambil data faktur dan transaksi jual
        $faktur = FakturOutlet::with('barangs')
            ->where('nomor_faktur', $nomor_faktur)
            ->firstOrFail();

        $transaksiJuals = TransaksiJualOutlet::with('barang')
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
        $pdf = \PDF::loadView('pages.transaksi-faktur-outlet.print', compact('faktur', 'transaksiJuals', 'totalHarga'));

        // Unduh atau tampilkan PDF
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
                // ... tambahkan perbandingan lain jika perlu ...

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
                $faktur->delete();
            });
            return redirect()->back()->with('success', 'Faktur dan data terkait berhasil dihapus');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    // Menambahkan bukti transfer
    public function storeBukti(Request $request)
    {
        $request->validate([
            't_faktur_id' => 'required|exists:t_faktur_outlet,id',
            'keterangan' => 'string|max:255',
            'nominal' => 'required|numeric', // Changed 'number' to 'numeric' for better validation
            'foto' => 'required|image'
        ]);
    
        $path = $request->file('foto')->store('faktur_bukti', 'public');
    
        // Create the new FakturBuktiOutlet record
        $fakturBukti = FakturBuktiOutlet::create([
            't_faktur_id' => $request->t_faktur_id,
            'keterangan' => $request->keterangan,
            'nominal' => $request->nominal,
            'foto' => $path
        ]);
    
        // Calculate the total nominal of all FakturBuktiOutlet records associated with the given t_faktur_id
        $totalNominal = FakturBuktiOutlet::where('t_faktur_id', $request->t_faktur_id)->sum('nominal');
    
        // Retrieve the FakturOutlet record
        $faktur = FakturOutlet::find($request->t_faktur_id);
    
        // Check if the total nominal is equal to or greater than the total in the FakturOutlet model
        if ($totalNominal >= $faktur->total) {
            $faktur->is_lunas = 1;
            $faktur->update();
        } else {
            $faktur->is_lunas = 0;
            $faktur->update();
        }
    
        return back()->with('success', 'Bukti transfer berhasil ditambahkan.');
    }      

    // Menghapus bukti transfer
    public function deleteBukti($id)
    {
        $bukti = FakturBuktiOutlet::findOrFail($id);
        Storage::disk('public')->delete($bukti->foto);
        
        $tFakturId = $bukti->t_faktur_id;
        $bukti->delete();

        $totalNominal = FakturBuktiOutlet::where('t_faktur_id', $tFakturId)->sum('nominal');
        $faktur = FakturOutlet::find($tFakturId);

        if ($totalNominal >= $faktur->total) {
            $faktur->is_lunas = 1;
            $faktur->update();
        } else {
            $faktur->is_lunas = 0;
            $faktur->update();
        }

        return back()->with('success', 'Bukti transfer berhasil dihapus.');
    }

    public function uploadBukti(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:t_faktur_outlet,id',
            'bukti_tf' => 'required|image|mimes:jpeg,png,jpg|max:10240'
        ]);

        $faktur = FakturOutlet::findOrFail($request->id);

        // Simpan gambar di folder 'bukti_transfer'
        if ($request->hasFile('bukti_tf')) {
            $file = $request->file('bukti_tf');
            $filePath = $file->store('bukti_transfer', 'public');

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
            // Ambil faktur beserta transaksi jual dan barang-nya
            $faktur = FakturOutlet::with('transaksiJuals.barang')->where('id', $id)->firstOrFail();

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

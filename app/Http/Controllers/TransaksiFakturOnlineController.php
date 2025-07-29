<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\Gudang;
use App\Models\ReturnBarang;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\FakturOnline;
use App\Models\TransaksiJualOnline;
use App\Models\TokpedDataDeposit;
use App\Models\TokpedDataOrder;
use App\Models\HistoryEditFakturOnline;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class TransaksiFakturOnlineController extends Controller
{
    public function index(Request $request)
    {
        $query = FakturOnline::withCount(['barangs as total_barang'])->orderBy('tgl_jual', 'desc');

        $daftarGudang = ['POD', 'PPY', 'JJ', 'NAR'];

        if ($request->filled('kode_faktur')) {
            $kodeFaktur = $request->kode_faktur;

            if (in_array($kodeFaktur, $daftarGudang)) {
                // Jika kode faktur sesuai daftar, filter seperti biasa
                $query->where('title', 'like', "$kodeFaktur-%");
            } else {
                // Jika "Lain-Lain" dipilih (kode tidak ada dalam daftar), tampilkan semua faktur kecuali yang terdaftar
                $query->where(function ($q) use ($daftarGudang) {
                    foreach ($daftarGudang as $kode) {
                        $q->where('title', 'not like', "$kode-%");
                    }
                });
            }
        }

        // Filter berdasarkan rentang tanggal
        if ($request->filled('tanggal_mulai') && $request->filled('tanggal_selesai')) {
            $query->whereBetween('tgl_jual', [$request->tanggal_mulai, $request->tanggal_selesai]);
        }
        if ($request->filled('cek')) {
            $query->where('is_finish', $request->cek == 'Sudah_Dicek' ? 1 : 0);
        }

        $fakturs = $query->get();
        $roleUser = optional(Auth::user())->role;

        return view('pages.transaksi-faktur-online.index', compact('fakturs', 'roleUser'));
    }

    public function show($nomor_faktur)
    {
        $faktur = FakturOnline::with('barangs')
            ->where('id', $nomor_faktur)
            ->firstOrFail();

        $transaksiJualsOriginal = TransaksiJualOnline::with('barang')
            ->where('faktur_online_id', $nomor_faktur)
            ->get();

        $uniqueCleanedInvoices = $transaksiJualsOriginal->pluck('invoice')
            ->filter()
            ->map(function ($invoice) {
                $clean = preg_replace('/\D/', '', trim($invoice));
                return Str::substr($clean, -7);
            })
            ->unique()
            ->toArray();

        $uangMasukPerInvoice = [];
        if (!empty($uniqueCleanedInvoices)) {
            $uangMasukPerInvoice = TokpedDataDeposit::where(function ($query) use ($uniqueCleanedInvoices) {
                foreach ($uniqueCleanedInvoices as $inv) {
                    $query->orWhereRaw("RIGHT(REGEXP_REPLACE(TRIM(invoice_end), '[^0-9]', ''), 7) = ?", [$inv]);
                }
            })
            ->selectRaw('invoice_end, SUM(nominal) as total_uang_masuk, MIN(date) as tanggal_masuk')
            ->groupBy('invoice_end')
            ->get()
            ->mapWithKeys(function ($item) {
                $cleanInvoiceEnd = preg_replace('/\D/', '', trim($item->invoice_end));
                $last7DigitsKey = Str::substr($cleanInvoiceEnd, -7);
                return [$last7DigitsKey => $item];
            });
        }
        
        // Ambil Tanggal Pembatalan
        $cancellationDatesPerInvoice = [];
        if (!empty($uniqueCleanedInvoices)) {
            $cancellationDatesPerInvoice = TokpedDataOrder::whereIn(DB::raw("RIGHT(REGEXP_REPLACE(TRIM(invoice_number), '[^0-9]', ''), 7)"), $uniqueCleanedInvoices)
                ->whereNotNull('cancelled_at')
                ->select('invoice_number', 'cancelled_at')
                ->get()
                ->mapWithKeys(function ($item) {
                    $cleanInvoiceNumber = preg_replace('/\D/', '', trim($item->invoice_number));
                    $last7DigitsKey = Str::substr($cleanInvoiceNumber, -7);
                    // Ambil yang terbaru jika ada duplikat, atau sesuaikan logika jika perlu
                    return [$last7DigitsKey => Carbon::parse($item->cancelled_at)->translatedFormat('j F Y')]; 
                });
        }

        $transaksiJuals = $transaksiJualsOriginal->sortBy(function ($item) use ($uangMasukPerInvoice) {
            if (empty($item->invoice)) return now()->addYears(100)->timestamp;
            $cleanItemInvoice = preg_replace('/\D/', '', trim($item->invoice));
            $lookupKey = Str::substr($cleanItemInvoice, -7);
            $tanggal = optional($uangMasukPerInvoice[$lookupKey] ?? null)->tanggal_masuk ?? now()->addYears(100);
            return Carbon::parse($tanggal)->timestamp;
        })->values();

        $transaksiJuals = $transaksiJuals->map(function ($trx) use ($faktur) { 
            $returnBarang = ReturnBarang::where('lok_spk', $trx->lok_spk)
                ->with('returnModel')
                ->orderByDesc('id')
                ->first();

            if ($returnBarang && $returnBarang->returnModel && Carbon::parse($returnBarang->returnModel->tgl_return)->gt(Carbon::parse($faktur->tgl_jual))) {
                $trx->tgl_return = $returnBarang->returnModel->tgl_return;
            } else {
                $trx->tgl_return = null;
            }
            return $trx;
        });

        $roleUser = optional(Auth::user())->role;

        return view('pages.transaksi-faktur-online.detail', compact(
            'faktur',
            'transaksiJuals',
            'roleUser',
            'uangMasukPerInvoice',
            'cancellationDatesPerInvoice'
        ));
    }

    public function printPdf($nomor_faktur)
    {
        $roleUser = optional(Auth::user())->role;
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
        $pdf = \PDF::loadView('pages.transaksi-faktur-online.print', compact('faktur', 'transaksiJuals', 'totalHarga', 'roleUser'));

        // Unduh atau tampilkan PDF
        return $pdf->stream('Faktur_Penjualan_Online_' . $faktur->id . '.pdf');
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'toko' => 'required|string|max:255',
            'tgl_jual' => 'required|date',
            'petugas' => 'required|string|max:255',
            'grade' => 'required',
            'keterangan' => 'nullable|string',
        ]);

        try {
            DB::transaction(function () use ($validated, $id) {
                $faktur = FakturOnline::findOrFail($id);
                $perubahan = [];

                if ($faktur->title !== $validated['title']) {
                    $perubahan[] = "Title diubah dari '{$faktur->title}' menjadi '{$validated['title']}'";
                }
                if ($faktur->toko !== $validated['toko']) {
                    $perubahan[] = "Toko diubah dari '{$faktur->toko}' menjadi '{$validated['toko']}'";
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

                if (!empty($perubahan)) {
                    HistoryEditFakturOnline::create([
                        'faktur_id' => $faktur->id,
                        'update'    => implode('<br>', $perubahan),
                        'user_id'   => auth()->id(),
                    ]);
                }

                $faktur->update($validated);
            });

            return redirect()->route('transaksi-faktur-online.index')->with('success', 'Faktur berhasil diupdate');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            DB::transaction(function () use ($id) {
                $faktur = FakturOnline::findOrFail($id);
                $lokSpkList = TransaksiJualOnline::where('faktur_online_id', $id)->pluck('lok_spk');

                HistoryEditFakturOnline::create([
                    'faktur_id' => $faktur->id,
                    'update'    => "Menghapus faktur dengan Title: {$faktur->title}",
                    'user_id'   => auth()->id(),
                ]);

                TransaksiJualOnline::where('faktur_online_id', $id)->delete();

                if ($lokSpkList->isNotEmpty()) {
                    Barang::whereIn('lok_spk', $lokSpkList)
                        ->update(['status_barang' => 1, 'no_faktur' => null, 'harga_jual' => 0]);
                }
                
                $faktur->delete();
            });

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
            // Ambil faktur beserta transaksi jual dan barang-nya
            $faktur = FakturOnline::with('transaksiJuals.barang')->where('id', $id)->firstOrFail();

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

    public function tandaiBelumDicek($id)
    {
        try {
            // Ambil faktur beserta transaksi jual dan barang-nya
            $faktur = FakturOnline::with('transaksiJuals.barang')->where('id', $id)->firstOrFail();

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
            'POD' => 'Toko Podomoro',
            'PPY' => 'Toko Popeye',
            'JJ'  => 'Toko JJ',
            'NAR' => 'Toko Naruto',
            'LN'  => 'Toko Lain Lain'
        ];
    
        // Ambil filter dari request
        $filterGudang = $request->input('gudang');
        $filterBulan = $request->input('bulan');
    
        // Subquery untuk menghitung total barang per faktur
        $subquery = TransaksiJualOnline::selectRaw("faktur_online_id, COUNT(*) as total_barang")
            ->groupBy('faktur_online_id');
    
        // Query utama untuk rekap faktur online
        $query = FakturOnline::selectRaw("
                LEFT(title, LOCATE('-', title) - 1) as kode_gudang, 
                DATE_FORMAT(tgl_jual, '%m-%Y') as bulan_sort, 
                DATE_FORMAT(tgl_jual, '%Y-%m') as bulan_display, 
                SUM(total) as total_pendapatan, 
                COALESCE(SUM(sub.total_barang), 0) as total_barang
            ")
            ->leftJoinSub($subquery, 'sub', function ($join) {
                $join->on('t_faktur_online.id', '=', 'sub.faktur_online_id');
            })
            ->groupBy('kode_gudang', 'bulan_sort', 'bulan_display');
    
        // Terapkan filter jika ada input
        if (!empty($filterGudang)) {
            $query->having('kode_gudang', '=', $filterGudang);
        }
        if (!empty($filterBulan)) {
            $query->having('bulan_display', '=', $filterBulan);
        }        
    
        // Eksekusi query dan urutkan hasilnya
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
    
        return view('pages.transaksi-faktur-online.rekap', compact('rekaps', 'daftarGudang', 'filterGudang', 'filterBulan'));
    }

    public function printKesimpulan(Request $request)
    {
        // 1. Ambil query dasar dan relasi yang dibutuhkan
        $query = FakturOnline::withCount(['barangs as total_barang'])
            ->orderBy('tgl_jual', 'asc'); // Urutkan dari terlama untuk subtotal

        // 2. Terapkan SEMUA filter yang sama persis dengan method index()
        $daftarGudang = ['POD', 'PPY', 'JJ', 'NAR'];

        if ($request->filled('kode_faktur')) {
            $kodeFaktur = $request->kode_faktur;
            if (in_array($kodeFaktur, $daftarGudang)) {
                $query->where('title', 'like', "$kodeFaktur-%");
            } else { // "Lain-Lain"
                $query->where(function ($q) use ($daftarGudang) {
                    foreach ($daftarGudang as $kode) {
                        $q->where('title', 'not like', "$kode-%");
                    }
                });
            }
        }

        // Filter berdasarkan rentang tanggal
        if ($request->filled('tanggal_mulai') && $request->filled('tanggal_selesai')) {
            $query->whereBetween('tgl_jual', [$request->tanggal_mulai, $request->tanggal_selesai]);
        }
        
        // Filter berdasarkan status cek
        if ($request->filled('cek')) {
            $query->where('is_finish', $request->cek == 'Sudah_Dicek' ? 1 : 0);
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
        $pdf = \PDF::loadView('pages.transaksi-faktur-online.print-kesimpulan', compact(
            'fakturs',
            'totalJumlahBarang',
            'totalHargaKeseluruhan',
            'rentangTanggal'
        ));
        
        // 6. Tampilkan PDF di browser
        return $pdf->stream('kesimpulan-faktur-online-' . time() . '.pdf');
    }
}

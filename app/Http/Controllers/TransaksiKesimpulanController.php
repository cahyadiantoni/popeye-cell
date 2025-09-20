<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\Barang;
use App\Models\FakturBawah;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\KesimpulanBawah;
use App\Models\FakturKesimpulan;
use App\Models\BuktiTfBawah;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TransaksiKesimpulanController extends Controller
{
    public function index(Request $request)
    {
        // PERUBAHAN 1: Menambahkan withSum untuk relasi 'payments'
        $query = KesimpulanBawah::with(['fakturKesimpulans.faktur.barangs'])
            ->withSum('bukti as total_nominal_bukti', 'nominal') // Total dari bukti manual
            ->withSum(['payments as total_payment_online' => function($q) { // Total dari payment online
                $q->whereIn('status', ['settlement', 'capture']);
            }], 'amount')
            ->orderByDesc('tgl_jual');
    
        if ($request->filled('tanggal_mulai') && $request->filled('tanggal_selesai')) {
            $query->whereBetween('tgl_jual', [$request->tanggal_mulai, $request->tanggal_selesai]);
        }

        if ($request->filled('status')) {
            $query->where('is_lunas', $request->status == 'Lunas' ? 1 : 0);
        }

        if ($request->filled('cek')) {
            $query->where('is_finish', $request->cek == 'Sudah_Dicek' ? 1 : 0);
        }
    
        $kesimpulans = $query->get();
    
        // PERUBAHAN 2: Logika kalkulasi disederhanakan
        foreach ($kesimpulans as $kesimpulan) {
            $totalPaid = ($kesimpulan->total_nominal_bukti ?? 0) + ($kesimpulan->total_payment_online ?? 0);
            $newIsLunas = ($totalPaid >= $kesimpulan->grand_total) ? 1 : 0;

            if ($kesimpulan->is_lunas !== $newIsLunas) {
                $kesimpulan->is_lunas = $newIsLunas;
                $kesimpulan->save();
            }

            // Tambahkan atribut total_nominal untuk ditampilkan di view (menggunakan nama yang konsisten)
            $kesimpulan->total_nominal = $totalPaid;
        }

        $roleUser = optional(Auth::user())->role;
    
        return view('pages.transaksi-kesimpulan.index', compact('kesimpulans', 'roleUser'));
    }   

    public function create()
    {
        $fakturs = FakturBawah::withCount(['barangs as total_barang'])
            ->where('is_finish', 0)
            ->whereDoesntHave('fakturKesimpulan')
            ->orderBy('tgl_jual', 'desc')
            ->get();

        return view('pages.transaksi-kesimpulan.create', compact('fakturs'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'tgl_jual' => 'required|date',
            'potongan_kondisi' => 'nullable|numeric',
            'diskon' => 'nullable|numeric',
            'total' => 'required|numeric',
            'grand_total' => 'required|numeric',
            'keterangan' => 'nullable|string',
            'faktur_id' => 'required|array',
            'fotos' => 'nullable|array',
            'fotos.*' => 'nullable|image|max:2048',
            'nominals' => 'nullable|array',
            'nominals.*' => 'nullable|numeric|min:1',
        ]);

        $existingFaktur = FakturKesimpulan::whereIn('faktur_id', $request->faktur_id)->exists();
        if ($existingFaktur) {
            return back()->withInput()->with('error', 'Salah satu faktur sudah digunakan dalam kesimpulan lain.');
        }

        DB::beginTransaction();

        try {
            $tglJual = $request->input('tgl_jual');
            $bulanTahun = date('my', strtotime($tglJual));
            $prefix = 'K-BW-' . $bulanTahun;
            $count = KesimpulanBawah::where('nomor_kesimpulan', 'like', "$prefix-%")->count();
            $noUrut = str_pad($count + 1, 3, '0', STR_PAD_LEFT);
            $nomor_kesimpulan = "$prefix-$noUrut";

            $kesimpulan = KesimpulanBawah::create([
                'nomor_kesimpulan' => $nomor_kesimpulan,
                'tgl_jual' => $tglJual,
                'total' => $request->input('total'),
                'grand_total' => $request->input('grand_total'),
                'potongan_kondisi' => $request->input('potongan_kondisi') ?? 0,
                'diskon' => $request->input('diskon') ?? 0,
                'keterangan' => $request->input('keterangan'),
                'is_lunas' => 0,
            ]);

            foreach ($request->input('faktur_id') as $fakturId) {
                FakturKesimpulan::create([
                    'kesimpulan_id' => $kesimpulan->id,
                    'faktur_id' => $fakturId,
                ]);
            }

            if ($request->has('nominals')) {
                foreach ($request->input('nominals') as $key => $nominal) {
                    if ($request->hasFile("fotos.{$key}") && is_numeric($nominal)) {
                        $path = $request->file("fotos.{$key}")->store('bukti_transfer_kesimpulan', 'public');
                        BuktiTfBawah::create([
                            'kesimpulan_id' => $kesimpulan->id,
                            'nominal' => $nominal,
                            'foto' => $path,
                            'keterangan' => 'Transfer - Bukti ' . ($key + 1),
                        ]);
                    }
                }
            }

            $totalNominal = array_sum($request->input('nominals', []));
            $kesimpulan->is_lunas = ($totalNominal >= $kesimpulan->grand_total && $kesimpulan->grand_total > 0) ? 1 : 0;
            $kesimpulan->save();

            DB::commit();
            return redirect()->route('transaksi-kesimpulan.show', ['kesimpulan_id' => $kesimpulan->id])
                ->with('success', 'Kesimpulan berhasil disimpan!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal menyimpan kesimpulan: ' . $e->getMessage());
        }
    }
    
    public function show($kesimpulan_id)
    {
        // PERUBAHAN 3: Menambahkan relasi 'payments' dan kalkulasi total
        $kesimpulan = KesimpulanBawah::with([
            'bukti',
            'payments', // Eager load payments
            'fakturKesimpulans.faktur.barangs'
        ])
        ->where('id', $kesimpulan_id)
        ->firstOrFail();

        $totalBuktiManual = $kesimpulan->bukti->sum('nominal');
        $totalPaymentOnline = $kesimpulan->payments->whereIn('status', ['settlement', 'capture'])->sum('amount');
        $totalPaid = $totalBuktiManual + $totalPaymentOnline;
        
        $newIsLunas = ($totalPaid >= $kesimpulan->grand_total) ? 1 : 0;
        if ($kesimpulan->is_lunas !== $newIsLunas) {
            $kesimpulan->is_lunas = $newIsLunas;
            $kesimpulan->save();
        }
        
        // Tambahkan total_nominal ke objek untuk view
        $kesimpulan->total_nominal = $totalPaid;

        $fakturs = FakturKesimpulan::with('faktur')
            ->where('kesimpulan_id', $kesimpulan_id)
            ->get();

        $roleUser = optional(Auth::user())->role;

        return view('pages.transaksi-kesimpulan.detail', compact('kesimpulan', 'fakturs', 'roleUser'));
    }

    public function printAllPdf($kesimpulan_id)
    {
        // 1. Ambil data Kesimpulan dengan relasi Bukti dan FakturKesimpulan
        $kesimpulan = KesimpulanBawah::with(['bukti', 'fakturKesimpulans.faktur'])
            ->where('id', $kesimpulan_id)
            ->firstOrFail();

        // 2. Ambil data TransaksiJualBawah untuk setiap Faktur yang terkait
        $faktursDataForView = [];
        $nomorFakturs = $kesimpulan->fakturKesimpulans->pluck('faktur.nomor_faktur')->filter()->unique(); // Ambil nomor faktur unik

        foreach ($nomorFakturs as $nomor_faktur) {
            $faktur = $kesimpulan->fakturKesimpulans->where('faktur.nomor_faktur', $nomor_faktur)->first()->faktur;

            $transaksiJuals = TransaksiJualBawah::with('barang')
                ->where('nomor_faktur', $nomor_faktur)
                ->get();

            // Hitung subtotal kumulatif dan total harga untuk faktur ini (sesuai logika print faktur asli)
            $subtotalKumulatif = 0;
            $transaksiJuals = $transaksiJuals->map(function ($transaksi) use (&$subtotalKumulatif) {
                $subtotalKumulatif += $transaksi->harga;
                $transaksi->subtotal = $subtotalKumulatif; // Tambahkan field subtotal kumulatif
                return $transaksi;
            });
            $totalHargaFaktur = $transaksiJuals->sum('harga'); // Total harga per faktur

            $faktursDataForView[] = [
                'faktur' => $faktur,
                'transaksiJuals' => $transaksiJuals,
                'totalHarga' => $totalHargaFaktur,
            ];
        }

        $roleUser = optional(Auth::user())->role; // Jika masih dibutuhkan di view

        // 3. Kirim semua data ke template PDF gabungan
        $pdf = \Pdf::loadView('pages.transaksi-kesimpulan.print-all', compact('kesimpulan', 'faktursDataForView', 'roleUser'));

        // 4. Unduh atau tampilkan PDF
        return $pdf->stream('Kesimpulan_Faktur_Bukti_' . $kesimpulan->nomor_kesimpulan . '.pdf');
    }

    public function printPdf($kesimpulan_id)
    {
        $kesimpulan = KesimpulanBawah::with([
            'bukti',
            'fakturKesimpulans.faktur.barangs'
        ])
        ->where('id', $kesimpulan_id)
        ->firstOrFail();

        if (empty($kesimpulan->total_nominal)) {
            $total_nominal = 0; 
        } else {
            $total_nominal = $kesimpulan->total_nominal;
        }

        // Update is_lunas based on the comparison
        if ($total_nominal >= $kesimpulan->grand_total) {
            $kesimpulan->is_lunas = 1; // Update is_lunas to 1
            $kesimpulan->update(); // Update is_lunas to 1
        } else {
            $kesimpulan->is_lunas = 0; // Update is_lunas to 0
            $kesimpulan->update(); // Update is_lunas to 0
        }

        $fakturs = FakturKesimpulan::with('faktur')
            ->where('kesimpulan_id', $kesimpulan_id)
            ->get();

        $roleUser = optional(Auth::user())->role;

        // Kirim data ke template PDF
        $pdf = \PDF::loadView('pages.transaksi-kesimpulan.print', compact('kesimpulan', 'fakturs', 'roleUser'));

        // Unduh atau tampilkan PDF
        return $pdf->stream('Faktur_Penjualan_' . $kesimpulan->nomor_kesimpulan . '.pdf');
    }  

    public function destroy($kesimpulan_id)
    {
        try {
            // Cari faktur berdasarkan kesimpulan_id
            $faktur = KesimpulanBawah::where('id', $kesimpulan_id)->firstOrFail();
    
            // Hapus semua baris di FakturKesimpulan yang memiliki kesimpulan_id tersebut
            FakturKesimpulan::where('kesimpulan_id', $kesimpulan_id)->delete();
    
            $faktur->delete();
    
            return redirect()->back()->with('success', 'Kesimpulan dan data terkait berhasil dihapus');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function storeBukti(Request $request)
    {
        $request->validate([
            'kesimpulan_id' => 'required|exists:t_kesimpulan_bawah,id',
            'keterangan' => 'string|max:255',
            'nominal' => 'required|numeric',
            'foto' => 'required|image'
        ]);
    
        $path = $request->file('foto')->store('bukti_transfer_kesimpulan', 'public');
    
        BuktiTfBawah::create([
            'kesimpulan_id' => $request->kesimpulan_id,
            'keterangan' => $request->keterangan,
            'nominal' => $request->nominal,
            'foto' => $path
        ]);
    
        // PERUBAHAN 4: Logika update status lunas setelah tambah bukti
        $this->updateLunasStatus($request->kesimpulan_id);
    
        return back()->with('success', 'Bukti transfer berhasil ditambahkan.');
    }    

    public function deleteBukti($id)
    {
        $bukti = BuktiTfBawah::findOrFail($id);
        Storage::disk('public')->delete($bukti->foto);
        
        $kesimpulanId = $bukti->kesimpulan_id;
        $bukti->delete();

        // PERUBAHAN 5: Logika update status lunas setelah hapus bukti
        $this->updateLunasStatus($kesimpulanId);

        return back()->with('success', 'Bukti transfer berhasil dihapus.');
    }

    // PERUBAHAN 6: Helper method untuk update status lunas
    private function updateLunasStatus($kesimpulanId)
    {
        $kesimpulan = KesimpulanBawah::with('payments')->find($kesimpulanId);
        if (!$kesimpulan) return;

        $totalBuktiManual = $kesimpulan->bukti()->sum('nominal');
        $totalPaymentOnline = $kesimpulan->payments->whereIn('status', ['settlement', 'capture'])->sum('amount');
        $totalPaid = $totalBuktiManual + $totalPaymentOnline;
        
        $newIsLunas = ($totalPaid >= $kesimpulan->grand_total) ? 1 : 0;

        if ($kesimpulan->is_lunas !== $newIsLunas) {
            $kesimpulan->is_lunas = $newIsLunas;
            $kesimpulan->save();
        }
    }

    public function tandaiSudahDicek($id)
    {
        DB::beginTransaction();

        try {
            $kesimpulan = KesimpulanBawah::with('fakturKesimpulans.faktur.transaksiJuals.barang')->findOrFail($id);

            foreach ($kesimpulan->fakturKesimpulans as $fakturKesimpulan) {
                $faktur = $fakturKesimpulan->faktur;

                // PENAMBAHAN KONDISI:
                // Hanya jalankan kode di bawah jika faktur ada DAN is_finish-nya BUKAN 1.
                if ($faktur && $faktur->is_finish != 1) {

                    // 1. Ubah status is_finish pada faktur
                    $faktur->is_finish = 1;
                    $faktur->save();

                    // 2. Ubah status_barang untuk semua item terkait
                    foreach ($faktur->transaksiJuals as $transaksi) {
                        if ($transaksi->barang) {
                            $transaksi->barang->status_barang = 2;
                            $transaksi->barang->save();
                        }
                    }
                }
                // Jika is_finish sudah 1, blok kode di atas akan dilewati.
            }

            // Tetap tandai kesimpulan sebagai selesai setelah loop berakhir.
            $kesimpulan->is_finish = 1;
            $kesimpulan->save();

            DB::commit();

            // Pesan sukses yang lebih akurat
            return redirect()->back()->with('success', 'Proses selesai. Status kesimpulan dan faktur yang relevan telah diperbarui.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function printKesimpulan(Request $request)
    {
        $query = KesimpulanBawah::with([
            'bukti',
            'fakturKesimpulans.faktur.barangs'
        ])->orderBy('tgl_jual', 'asc');

        // Filter: tanggal
        if ($request->filled('tanggal_mulai') && $request->filled('tanggal_selesai')) {
            $query->whereBetween('tgl_jual', [$request->tanggal_mulai, $request->tanggal_selesai]);
        }

        // Filter: status (is_lunas)
        if ($request->filled('status')) {
            $query->where('is_lunas', $request->status === 'Lunas' ? 1 : 0);
        }

        // Filter: cek (is_finish)
        if ($request->filled('cek')) {
            $query->where('is_finish', $request->cek === 'Sudah_Dicek' ? 1 : 0);
        }

        $kesimpulans = $query->get();

        // (Opsional) normalisasi is_lunas seperti index()
        foreach ($kesimpulans as $k) {
            $dibayar = (float) ($k->total_nominal ?? 0);
            $k->is_lunas = $dibayar >= (float) $k->grand_total ? 1 : 0;
        }

        // Siapkan rows per Kesimpulan, sambil SKIP jumlah_barang == 0
        $rows = [];
        foreach ($kesimpulans as $k) {
            $fakturs = $k->fakturKesimpulans
                ->filter(fn($fk) => $fk->faktur)
                ->pluck('faktur');

            // Hitung jumlah barang via accessor di model (sudah kamu sediakan)
            $jumlahBarang = (int) ($k->total_barang ?? 0);

            // Skip kalau jumlah barang 0
            if ($jumlahBarang <= 0) {
                continue;
            }

            // Invoice = keterangan faktur (comma-separated)
            $invoiceList = $fakturs->pluck('keterangan')
                ->filter(fn($v) => filled($v))
                ->implode(', ');

            // Petugas = unique petugas dari faktur (comma-separated)
            $petugasList = $fakturs->pluck('petugas')
                ->filter(fn($v) => filled($v))
                ->unique()
                ->values()
                ->implode(', ');

            // Total (sebelum potongan & diskon) ambil dari field KesimpulanBawah.total
            $totalAwal = (float) ($k->total ?? 0);

            $rows[] = [
                'tgl'            => $k->tgl_jual,
                'invoice'        => $invoiceList,
                'petugas'        => $petugasList,
                'jumlah_barang'  => $jumlahBarang,
                'total'          => $totalAwal,                    // NEW: kolom Total
                'potongan'       => (float) ($k->potongan_kondisi ?? 0),
                'diskon'         => (float) ($k->diskon ?? 0),     // %
                'transfer'       => (float) ($k->grand_total ?? 0),
                'note'           => '',
            ];
        }

        // Info header & totals pakai rows yang sudah ter-filter (tanpa yang jumlah_barang=0)
        if (!empty($rows)) {
            // tgl awal/akhir dari rows
            $tgls = collect($rows)->pluck('tgl')->sort()->values();
            $tanggalMulai   = Carbon::parse($tgls->first())->translatedFormat('d M Y');
            $tanggalSelesai = Carbon::parse($tgls->last())->translatedFormat('d M Y');
        } else {
            $tanggalMulai = $tanggalSelesai = 'N/A';
        }
        $rentangTanggal = ($tanggalMulai === $tanggalSelesai) ? $tanggalMulai : ($tanggalMulai.' - '.$tanggalSelesai);

        $totalBarang    = collect($rows)->sum('jumlah_barang');
        $totalPotongan  = collect($rows)->sum('potongan');
        $totalTransfer  = collect($rows)->sum('transfer');

        $pdf = \PDF::loadView('pages.transaksi-kesimpulan.print-kesimpulan', [
            'rows'           => $rows,
            'rentangTanggal' => $rentangTanggal,
            'totalBarang'    => $totalBarang,
            'totalPotongan'  => $totalPotongan,
            'totalTransfer'  => $totalTransfer,
        ]);

        // Landscape
        $pdf->setPaper('a4', 'landscape');

        return $pdf->stream('kesimpulan-index-'.time().'.pdf');
    }

}

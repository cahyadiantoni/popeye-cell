<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\Barang;
use App\Models\FakturBukti;
use App\Models\FakturBawah;
use App\Models\Negoan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\KesimpulanBawah;
use App\Models\FakturKesimpulan;
use App\Models\BuktiTfBawah;
use App\Models\TransaksiJualBawah;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

class TransaksiKesimpulanController extends Controller
{
    public function index(Request $request)
    {
        $query = KesimpulanBawah::with([
            'bukti',
            'fakturKesimpulans.faktur.barangs'
        ])
        ->orderByDesc('tgl_jual');
    
        // Filter berdasarkan rentang tanggal
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
    
        // Loop through each Faktur record to update is_lunas
        foreach ($kesimpulans as $kesimpulan) {
            // Set total_nominal to 0 if it is empty
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
        }

        $roleUser = optional(Auth::user())->role;
    
        return view('pages.transaksi-kesimpulan.index', compact('kesimpulans', 'roleUser'));
    }    

    public function create()
    {
        $fakturs = FakturBawah::withCount(['barangs as total_barang'])
            ->where('is_finish', 0)
            ->whereDoesntHave('fakturKesimpulan') // Filter faktur yang belum ada di FakturKesimpulan
            ->orderBy('tgl_jual', 'desc')
            ->get();

        return view('pages.transaksi-kesimpulan.create', compact('fakturs'));
    }

    public function store(Request $request)
    {
        // Validasi yang disesuaikan untuk array
        $request->validate([
            'tgl_jual' => 'required|date',
            'potongan_kondisi' => 'nullable|numeric',
            'diskon' => 'nullable|numeric',
            'total' => 'required|numeric',
            'grand_total' => 'required|numeric',
            'keterangan' => 'nullable|string',
            'faktur_id' => 'required|array',
            'fotos' => 'nullable|array', // Validasi 'fotos' sebagai array
            'fotos.*' => 'nullable|image|max:2048', // Validasi setiap item di dalam array 'fotos'
            'nominals' => 'nullable|array', // Validasi 'nominals' sebagai array
            'nominals.*' => 'nullable|numeric|min:1', // Validasi setiap item di dalam array 'nominals'
        ]);

        // Cek duplikasi faktur_id
        $existingFaktur = FakturKesimpulan::whereIn('faktur_id', $request->faktur_id)->exists();

        if ($existingFaktur) {
            return back()->withInput()->with('error', 'Salah satu faktur sudah digunakan dalam kesimpulan lain.');
        }

        DB::beginTransaction();

        try {
            // Nomor Kesimpulan (tidak ada perubahan)
            $tglJual = $request->input('tgl_jual');
            $bulanTahun = date('my', strtotime($tglJual));
            $prefix = 'K-BW-' . $bulanTahun;
            $count = KesimpulanBawah::where('nomor_kesimpulan', 'like', "$prefix-%")->count();
            $noUrut = str_pad($count + 1, 3, '0', STR_PAD_LEFT);
            $nomor_kesimpulan = "$prefix-$noUrut";

            // Create Kesimpulan (tidak ada perubahan)
            $kesimpulan = KesimpulanBawah::create([
                'nomor_kesimpulan' => $nomor_kesimpulan,
                'tgl_jual' => $tglJual,
                'total' => $request->input('total'),
                'grand_total' => $request->input('grand_total'),
                'potongan_kondisi' => $request->input('potongan_kondisi') ?? 0,
                'diskon' => $request->input('diskon') ?? 0,
                'keterangan' => $request->input('keterangan'),
                'is_lunas' => 0, // default
            ]);

            // Simpan relasi faktur (tidak ada perubahan)
            foreach ($request->input('faktur_id') as $fakturId) {
                FakturKesimpulan::create([
                    'kesimpulan_id' => $kesimpulan->id,
                    'faktur_id' => $fakturId,
                ]);
            }

            if ($request->has('nominals')) {
                $nominals = $request->input('nominals');
                
                // Ubah cara iterasi: Loop melalui nominals, bukan fotos.
                foreach ($nominals as $key => $nominal) {
                    // Periksa apakah ada file yang diupload untuk kunci (key) ini DAN nominalnya valid.
                    if ($request->hasFile("fotos.{$key}") && is_numeric($nominal)) {
                        $file = $request->file("fotos.{$key}");
                        $path = $file->store('bukti_transfer_kesimpulan', 'public');

                        BuktiTfBawah::create([
                            'kesimpulan_id' => $kesimpulan->id,
                            'nominal' => $nominal,
                            'foto' => $path,
                            'keterangan' => 'Transfer - Bukti ' . ($key + 1),
                        ]);
                    }
                }
            }

            // Hitung total semua nominal dan tentukan status lunas (tidak ada perubahan)
            $totalNominal = array_sum($request->input('nominals', []));
            if ($totalNominal >= $kesimpulan->grand_total && $kesimpulan->grand_total > 0) {
                $kesimpulan->is_lunas = 1;
            } else {
                $kesimpulan->is_lunas = 0;
            }
            $kesimpulan->save();

            DB::commit();
            return redirect()->route('transaksi-kesimpulan.show', [
                'kesimpulan_id' => $kesimpulan->id
            ])->with('success', 'Kesimpulan berhasil disimpan!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal menyimpan kesimpulan: ' . $e->getMessage());
        }
    }
    
    public function show($kesimpulan_id)
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

    // Menambahkan bukti transfer
    public function storeBukti(Request $request)
    {
        $request->validate([
            'kesimpulan_id' => 'required|exists:t_kesimpulan_bawah,id',
            'keterangan' => 'string|max:255',
            'nominal' => 'required|numeric', // Changed 'number' to 'numeric' for better validation
            'foto' => 'required|image'
        ]);
    
        $path = $request->file('foto')->store('bukti_transfer_kesimpulan', 'public');
    
        // Create the new BuktiTfBawah record
        $fakturBukti = BuktiTfBawah::create([
            'kesimpulan_id' => $request->kesimpulan_id,
            'keterangan' => $request->keterangan,
            'nominal' => $request->nominal,
            'foto' => $path
        ]);
    
        // Calculate the total nominal of all BuktiTfBawah records associated with the given kesimpulan_id
        $totalNominal = BuktiTfBawah::where('kesimpulan_id', $request->kesimpulan_id)->sum('nominal');
    
        // Retrieve the KesimpulanBawah record
        $kesimpulan = KesimpulanBawah::find($request->kesimpulan_id);
    
        // Check if the total nominal is equal to or greater than the total in the KesimpulanBawah model
        if ($totalNominal >= $kesimpulan->total) {
            $kesimpulan->is_lunas = 1;
            $kesimpulan->update();
        } else {
            $kesimpulan->is_lunas = 0;
            $kesimpulan->update();
        }
    
        return back()->with('success', 'Bukti transfer berhasil ditambahkan.');
    }      

    // Menghapus bukti transfer
    public function deleteBukti($id)
    {
        $bukti = BuktiTfBawah::findOrFail($id);
        Storage::disk('public')->delete($bukti->foto);
        
        $tFakturId = $bukti->kesimpulan_id;
        $bukti->delete();

        $totalNominal = BuktiTfBawah::where('kesimpulan_id', $tFakturId)->sum('nominal');
        $faktur = KesimpulanBawah::find($tFakturId);

        if ($totalNominal >= $faktur->total) {
            $faktur->is_lunas = 1;
            $faktur->update();
        } else {
            $faktur->is_lunas = 0;
            $faktur->update();
        }

        return back()->with('success', 'Bukti transfer berhasil dihapus.');
    }

    public function tandaiSudahDicek($id)
    {
        DB::beginTransaction();

        try {
            $kesimpulan = KesimpulanBawah::with('fakturKesimpulans.faktur.transaksiJuals.barang')->findOrFail($id);

            foreach ($kesimpulan->fakturKesimpulans as $fakturKesimpulan) {
                $faktur = $fakturKesimpulan->faktur;

                if ($faktur) {
                    $faktur->is_finish = 1;
                    $faktur->save();

                    foreach ($faktur->transaksiJuals as $transaksi) {
                        if ($transaksi->barang) {
                            $transaksi->barang->status_barang = 2;
                            $transaksi->barang->save();
                        }
                    }
                }
            }

            $kesimpulan->is_finish = 1;
            $kesimpulan->save();

            DB::commit();

            return redirect()->back()->with('success', 'Kesimpulan, semua faktur terkait, dan status barang berhasil diperbarui.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
}

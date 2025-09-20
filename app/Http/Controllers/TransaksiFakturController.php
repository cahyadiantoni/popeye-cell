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
use App\Models\Faktur;
use App\Models\TransaksiJual;
use App\Models\HistoryEditFakturAtas;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use App\Exports\FakturExport;
use Illuminate\Support\Facades\DB;
use App\Exports\FakturGabunganExport;

class TransaksiFakturController extends Controller
{
    public function index(Request $request)
    {
        // --- PERUBAHAN DI SINI ---
        // Menggunakan withSum untuk payments agar lebih efisien daripada with()
        $query = Faktur::withCount(['barangs as total_barang'])
            ->withSum('bukti as total_nominal_bukti', 'nominal') // total dari bukti manual
            ->withSum(['payments as total_payment_online' => function($q) { // Menghitung total payment online
                $q->whereIn('status', ['settlement', 'capture']);
            }], 'amount')
            ->orderBy('tgl_jual', 'desc');

        $roleUser = optional(Auth::user())->role;
        $gudangId = optional(Auth::user())->gudang_id;

        if($roleUser == 'admin'){
            $daftarGudang = ['AT', 'TKP', 'VR', 'BW'];
                    
            if ($request->filled('kode_faktur')) {
                $kodeFaktur = $request->kode_faktur;
                if (in_array($kodeFaktur, $daftarGudang)) {
                    if ($kodeFaktur === 'AT') {
                        $query->where(function ($q) {
                            $q->where('nomor_faktur', 'like', 'AT-%')
                            ->orWhere('nomor_faktur', 'like', 'LN-%');
                        });
                    } 
                    else {
                        $query->where('nomor_faktur', 'like', "$kodeFaktur-%");
                    }
                } 
                else {
                    $query->where(function ($q) use ($daftarGudang) {
                        foreach ($daftarGudang as $kode) {
                            $q->where('nomor_faktur', 'not like', "$kode-%");
                        }
                        $q->where('nomor_faktur', 'not like', 'LN-%');
                    });
                }
            }
        }else{
            switch ($gudangId) {
                case 1:
                    $query->where('nomor_faktur', 'like', "BW-%");
                    break;
                case 2:
                    $query->where(function ($q) {
                        $q->where('nomor_faktur', 'like', 'AT-%')
                        ->orWhere('nomor_faktur', 'like', 'LN-%');
                    });
                    break;
                case 3:
                    $query->where('nomor_faktur', 'like', "TKP-%");
                    break;
                case 5:
                    $query->where('nomor_faktur', 'like', "VR-%");
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

        // --- PERUBAHAN DI SINI ---
        // Logika kalkulasi disederhanakan karena sudah menggunakan withSum
        foreach ($fakturs as $faktur) {
            // Atribut total_nominal_bukti dan total_payment_online otomatis ditambahkan oleh withSum
            $totalNominal = ($faktur->total_nominal_bukti ?? 0) + ($faktur->total_payment_online ?? 0);

            $newIsLunas = ($totalNominal >= $faktur->total) ? 1 : 0;
            
            // Lakukan update hanya jika status lunasnya berubah
            if ($faktur->is_lunas !== $newIsLunas) {
                $faktur->is_lunas = $newIsLunas;
                $faktur->save();
            }

            // Tambahkan atribut total_nominal untuk ditampilkan di view
            $faktur->total_nominal = $totalNominal;
        }

        return view('pages.transaksi-faktur.index', compact('fakturs', 'roleUser'));
    } 

    public function show($nomor_faktur)
    {
        // Method ini tidak perlu diubah, karena eager loading relasi 'payments'
        // sudah secara otomatis menangani relasi polymorphic untuk satu record.
        $faktur = Faktur::with(['barangs', 'bukti', 'payments'])
            ->where('nomor_faktur', $nomor_faktur)
            ->firstOrFail();

        $totalBuktiManual = $faktur->bukti->sum('nominal');
        $totalPaymentMidtrans = $faktur->payments
            ->whereIn('status', ['settlement', 'capture'])
            ->sum('amount');

        $totalNominal = $totalBuktiManual + $totalPaymentMidtrans;

        $newIsLunas = ($totalNominal >= $faktur->total) ? 1 : 0;

        if ($faktur->is_lunas !== $newIsLunas) {
            $faktur->is_lunas = $newIsLunas;
            $faktur->save();
        }

        $transaksiJuals = TransaksiJual::with('barang')
            ->where('nomor_faktur', $nomor_faktur)
            ->get();

        $roleUser = optional(Auth::user())->role;

        return view('pages.transaksi-faktur.detail', compact('faktur', 'transaksiJuals', 'roleUser', 'totalNominal'));
    }
    
    // ... method lainnya tidak perlu diubah karena tidak berinteraksi langsung dengan relasi 'payments'
    
    public function printPdf($nomor_faktur)
    {
        $roleUser = optional(Auth::user())->role;
        // Ambil data faktur dan transaksi jual
        $faktur = Faktur::with('barangs', 'bukti')
            ->where('nomor_faktur', $nomor_faktur)
            ->firstOrFail();

        $transaksiJuals = TransaksiJual::with('barang')
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
        $pdf = \PDF::loadView('pages.transaksi-faktur.print', compact('faktur', 'transaksiJuals', 'totalHarga', 'roleUser'));

        // Unduh atau tampilkan PDF
        return $pdf->stream('Faktur_Penjualan_' . $faktur->nomor_faktur . '.pdf');
    }

    public function update(Request $request, $id)
    {
        // Validasi data input
        $validated = $request->validate([
            'nomor_faktur' => 'required|string|max:255|unique:t_faktur,nomor_faktur,' . $id,
            'pembeli' => 'required|string|max:255',
            'tgl_jual' => 'required|date',
            'petugas' => 'required|string|max:255',
            'grade' => 'required',
            'keterangan' => 'nullable|string',
        ]);
        
        try {
            DB::transaction(function () use ($validated, $id, $request) { // <-- Mulai transaksi
                // Cari faktur berdasarkan ID
                $faktur = Faktur::findOrFail($id);
                $nomorFakturLama = $faktur->nomor_faktur;
                $perubahan = []; // Array untuk menampung deskripsi perubahan

                //--- LOGIKA UNTUK MENCATAT PERUBAHAN ---
                if ($faktur->nomor_faktur !== $validated['nomor_faktur']) {
                    $perubahan[] = "No Faktur diubah dari '{$faktur->nomor_faktur}' menjadi '{$validated['nomor_faktur']}'";
                }
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

                // Jika ada perubahan, catat ke history
                if (!empty($perubahan)) {
                    HistoryEditFakturAtas::create([
                        'faktur_id' => $faktur->id,
                        'update'    => implode('<br>', $perubahan), // Gabungkan semua perubahan jadi 1 string
                        'user_id'   => auth()->id(),
                    ]);
                }

                // Update data faktur
                $faktur->update($validated);

                // Update Transaksijual jika nomor faktur berubah
                if ($nomorFakturLama !== $validated['nomor_faktur']) {
                    TransaksiJual::where('nomor_faktur', $nomorFakturLama)
                        ->update(['nomor_faktur' => $validated['nomor_faktur']]);
                }
            }); // <-- Selesai transaksi

            session()->flash('success', 'Faktur berhasil diupdate');
            return redirect()->route('transaksi-faktur.index');
        } catch (\Exception $e) {
            session()->flash('error', 'Terjadi kesalahan: ' . $e->getMessage());
            return redirect()->back();
        }
    }   

    public function destroy($nomor_faktur)
    {
        try {
            DB::transaction(function () use ($nomor_faktur) { // <-- Mulai transaksi
                // Cari faktur berdasarkan nomor_faktur
                $faktur = Faktur::where('nomor_faktur', $nomor_faktur)->firstOrFail();
                
                // Ambil data lok_spk dari TransaksiJual berdasarkan nomor_faktur
                $lokSpkList = TransaksiJual::where('nomor_faktur', $nomor_faktur)->pluck('lok_spk');
                
                // <-- BUAT HISTORY SEBELUM DATA DIHAPUS -->
                HistoryEditFakturAtas::create([
                    'faktur_id' => $faktur->id,
                    'update'    => "Menghapus faktur dengan nomor: {$faktur->nomor_faktur}",
                    'user_id'   => auth()->id(),
                ]);

                // Hapus semua baris di TransaksiJual yang memiliki nomor_faktur tersebut
                TransaksiJual::where('nomor_faktur', $nomor_faktur)->delete();
                
                // Update data pada tabel Barang
                if ($lokSpkList->isNotEmpty()) {
                    Barang::whereIn('lok_spk', $lokSpkList)
                        ->update([
                            'status_barang' => 1,
                            'no_faktur' => null,
                            'harga_jual' => 0,
                        ]);
                }
                
                // Hapus Faktur
                $faktur->delete();
            }); // <-- Selesai transaksi

            return redirect()->back()->with('success', 'Faktur dan data terkait berhasil dihapus');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    // Menambahkan bukti transfer
    public function storeBukti(Request $request)
    {
        $request->validate([
            't_faktur_id' => 'required|exists:t_faktur,id',
            'keterangan' => 'string|max:255',
            'nominal' => 'required|numeric', // Changed 'number' to 'numeric' for better validation
            'foto' => 'required|image'
        ]);
    
        $path = $request->file('foto')->store('faktur_bukti', 'public');
    
        // Create the new FakturBukti record
        FakturBukti::create([
            't_faktur_id' => $request->t_faktur_id,
            'keterangan' => $request->keterangan,
            'nominal' => $request->nominal,
            'foto' => $path
        ]);
    
        return back()->with('success', 'Bukti transfer berhasil ditambahkan.');
    }   

    // Menghapus bukti transfer
    public function deleteBukti($id)
    {
        $bukti = FakturBukti::findOrFail($id);
        Storage::disk('public')->delete($bukti->foto);
        
        $bukti->delete();

        return back()->with('success', 'Bukti transfer berhasil dihapus.');
    }

    public function uploadBukti(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:t_faktur,id',
            'bukti_tf' => 'required|image|mimes:jpeg,png,jpg|max:10240'
        ]);

        $faktur = Faktur::findOrFail($request->id);

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
            $faktur = Faktur::with('transaksiJuals.barang')->where('id', $id)->firstOrFail();

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
            $faktur = Faktur::with('transaksiJuals.barang')->where('id', $id)->firstOrFail();

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
        $subquery = TransaksiJual::selectRaw("nomor_faktur, COUNT(*) as total_barang")
            ->groupBy('nomor_faktur');
    
        // Query utama
        $query = Faktur::selectRaw("
                LEFT(t_faktur.nomor_faktur, LOCATE('-', t_faktur.nomor_faktur) - 1) as kode_gudang, 
                DATE_FORMAT(t_faktur.tgl_jual, '%m-%Y') as bulan_sort, 
                DATE_FORMAT(t_faktur.tgl_jual, '%Y-%m') as bulan_display, 
                SUM(t_faktur.total) as total_pendapatan, 
                COALESCE(SUM(sub.total_barang), 0) as total_barang
            ")
            ->leftJoinSub($subquery, 'sub', function ($join) {
                $join->on('t_faktur.nomor_faktur', '=', 'sub.nomor_faktur');
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
    
        return view('pages.transaksi-faktur.rekap', compact('rekaps', 'daftarGudang', 'filterGudang', 'filterBulan'));
    }   
    
    public function printMultiple(Request $request)
    {
        $query = Faktur::with(['barangs', 'bukti', 'transaksiJuals.barang'])->orderBy('tgl_jual', 'desc');

        $roleUser = optional(Auth::user())->role;
        $gudangId = optional(Auth::user())->gudang_id;

        if ($roleUser == 'admin') {
            $daftarGudang = ['AT', 'TKP', 'VR', 'BW'];
                    
            if ($request->filled('kode_faktur')) {
                $kodeFaktur = $request->kode_faktur;
                if (in_array($kodeFaktur, $daftarGudang)) {
                    if ($kodeFaktur === 'AT') {
                        $query->where(function ($q) {
                            $q->where('nomor_faktur', 'like', 'AT-%')
                            ->orWhere('nomor_faktur', 'like', 'LN-%');
                        });
                    } 
                    else {
                        $query->where('nomor_faktur', 'like', "$kodeFaktur-%");
                    }
                } 
                else {
                    $query->where(function ($q) use ($daftarGudang) {
                        foreach ($daftarGudang as $kode) {
                            $q->where('nomor_faktur', 'not like', "$kode-%");
                        }
                        $q->where('nomor_faktur', 'not like', 'LN-%');
                    });
                }
            }
        } else {
            switch ($gudangId) {
                case 1: $query->where('nomor_faktur', 'like', "BW-%"); break;
                case 2: $query->where('nomor_faktur', 'like', "AT-%"); break;
                case 3: $query->where('nomor_faktur', 'like', "TKP-%"); break;
                case 5: $query->where('nomor_faktur', 'like', "VR-%"); break;
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

        // Ambil transaksi jual masing-masing faktur
        foreach ($fakturs as $faktur) {
            $faktur->transaksiJuals = TransaksiJual::with('barang')
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

        $pdf = \PDF::loadView('pages.transaksi-faktur.print-multiple', compact('fakturs'))
            ->setPaper('A4', 'portrait');

        return $pdf->stream('Daftar_Faktur.pdf');
    }

    public function exportGabungan(Request $request)
    {
        // 1. Mulai query, load relasi barang, dan urutkan tanggal terlama (ASC)
        $query = Faktur::with(['transaksiJuals.barang']) // Hanya butuh relasi barang
                       ->orderBy('tgl_jual', 'asc');   // Diubah ke 'asc' untuk tanggal terlama

        $roleUser = optional(Auth::user())->role;
        $gudangId = optional(Auth::user())->gudang_id;

        // 2. Salin SEMUA logika filter dari exportMultiple
        if ($roleUser == 'admin') {
            $daftarGudang = ['AT', 'TKP', 'VR', 'BW'];
                    
            if ($request->filled('kode_faktur')) {
                $kodeFaktur = $request->kode_faktur;
                if (in_array($kodeFaktur, $daftarGudang)) {
                    if ($kodeFaktur === 'AT') {
                        $query->where(function ($q) {
                            $q->where('nomor_faktur', 'like', 'AT-%')
                            ->orWhere('nomor_faktur', 'like', 'LN-%');
                        });
                    } 
                    else {
                        $query->where('nomor_faktur', 'like', "$kodeFaktur-%");
                    }
                } 
                else {
                    $query->where(function ($q) use ($daftarGudang) {
                        foreach ($daftarGudang as $kode) {
                            $q->where('nomor_faktur', 'not like', "$kode-%");
                        }
                        $q->where('nomor_faktur', 'not like', 'LN-%');
                    });
                }
            }
        } else {
            switch ($gudangId) {
                // Logika filter disalin persis dari exportMultiple
                case 1: $query->where('nomor_faktur', 'like', "BW-%"); break;
                case 2: $query->where('nomor_faktur', 'like', "AT-%"); break;
                case 3: $query->where('nomor_faktur', 'like', "TKP-%"); break;
                case 5: $query->where('nomor_faktur', 'like', "VR-%"); break;
            }
        }

        // Filter berdasarkan tanggal jika ada
        if ($request->filled('tanggal_mulai') && $request->filled('tanggal_selesai')) {
            $query->whereBetween('tgl_jual', [$request->tanggal_mulai, $request->tanggal_selesai]);
        }

        // Filter berdasarkan status (Lunas/Hutang)
        if ($request->filled('status')) {
            $query->where('is_lunas', $request->status == 'Lunas' ? 1 : 0);
        }

        // Filter berdasarkan status cek
        if ($request->filled('cek')) {
            $query->where('is_finish', $request->cek == 'Sudah_Dicek' ? 1 : 0);
        }

        // 3. Ambil data faktur sesuai query
        $fakturs = $query->get();

        // 4. Panggil class export gabungan yang baru
        // (Tidak perlu loop foreach untuk proses subtotal di sini)
        return Excel::download(new FakturGabunganExport($fakturs), 'faktur_atas_gabungan.xlsx');
    }

    public function exportMultiple(Request $request)
    {
        // Mulai query untuk mendapatkan faktur dan relasi yang dibutuhkan
        $query = Faktur::with(['barangs', 'bukti', 'transaksiJuals.barang'])->orderBy('tgl_jual', 'desc');

        $roleUser = optional(Auth::user())->role;
        $gudangId = optional(Auth::user())->gudang_id;

        // Filter berdasarkan role user
        if ($roleUser == 'admin') {
            $daftarGudang = ['AT', 'TKP', 'VR', 'BW'];
                    
            if ($request->filled('kode_faktur')) {
                $kodeFaktur = $request->kode_faktur;
                if (in_array($kodeFaktur, $daftarGudang)) {
                    if ($kodeFaktur === 'AT') {
                        $query->where(function ($q) {
                            $q->where('nomor_faktur', 'like', 'AT-%')
                            ->orWhere('nomor_faktur', 'like', 'LN-%');
                        });
                    } 
                    else {
                        $query->where('nomor_faktur', 'like', "$kodeFaktur-%");
                    }
                } 
                else {
                    $query->where(function ($q) use ($daftarGudang) {
                        foreach ($daftarGudang as $kode) {
                            $q->where('nomor_faktur', 'not like', "$kode-%");
                        }
                        $q->where('nomor_faktur', 'not like', 'LN-%');
                    });
                }
            }
        } else {
            switch ($gudangId) {
                case 1: $query->where('nomor_faktur', 'like', "BW-%"); break;
                case 2: $query->where('nomor_faktur', 'like', "AT-%"); break;
                case 3: $query->where('nomor_faktur', 'like', "TKP-%"); break;
                case 5: $query->where('nomor_faktur', 'like', "VR-%"); break;
            }
        }

        // Filter berdasarkan tanggal jika ada
        if ($request->filled('tanggal_mulai') && $request->filled('tanggal_selesai')) {
            $query->whereBetween('tgl_jual', [$request->tanggal_mulai, $request->tanggal_selesai]);
        }

        // Filter berdasarkan status (Lunas/Hutang)
        if ($request->filled('status')) {
            $query->where('is_lunas', $request->status == 'Lunas' ? 1 : 0);
        }

        if ($request->filled('cek')) {
            $query->where('is_finish', $request->cek == 'Sudah_Dicek' ? 1 : 0);
        }

        // Ambil data faktur sesuai query
        $fakturs = $query->get();

        // Ambil transaksi jual dan hitung subtotal
        foreach ($fakturs as $faktur) {
            $faktur->transaksiJuals = TransaksiJual::with('barang')
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
        return Excel::download(new FakturExport($fakturs), 'faktur_atas.xlsx');
    }

    public function printKesimpulan(Request $request)
    {
        $query = Faktur::withCount(['barangs as total_barang'])
            ->orderBy('tgl_jual', 'asc');

        $roleUser = optional(Auth::user())->role;
        $gudangId = optional(Auth::user())->gudang_id;

        if ($roleUser == 'admin') {
            $daftarGudang = ['AT', 'TKP', 'VR', 'BW'];
            if ($request->filled('kode_faktur')) {
                $kodeFaktur = $request->kode_faktur;
                if (in_array($kodeFaktur, $daftarGudang)) {
                    if ($kodeFaktur === 'AT') {
                        $query->where(function ($q) {
                            $q->where('nomor_faktur', 'like', 'AT-%')
                                ->orWhere('nomor_faktur', 'like', 'LN-%');
                        });
                    } else {
                        $query->where('nomor_faktur', 'like', "$kodeFaktur-%");
                    }
                } else {
                    $query->where(function ($q) use ($daftarGudang) {
                        foreach ($daftarGudang as $kode) {
                            $q->where('nomor_faktur', 'not like', "$kode-%");
                        }
                        $q->where('nomor_faktur', 'not like', 'LN-%');
                    });
                }
            }
        } else {
            switch ($gudangId) {
                case 1: $query->where('nomor_faktur', 'like', "BW-%"); break;
                case 2:
                    $query->where(function ($q) {
                        $q->where('nomor_faktur', 'like', 'AT-%')
                            ->orWhere('nomor_faktur', 'like', 'LN-%');
                    });
                    break;
                case 3: $query->where('nomor_faktur', 'like', "TKP-%"); break;
                case 5: $query->where('nomor_faktur', 'like', "VR-%"); break;
            }
        }
        
        if ($request->filled('tanggal_mulai') && $request->filled('tanggal_selesai')) {
            $query->whereBetween('tgl_jual', [$request->tanggal_mulai, $request->tanggal_selesai]);
        }
        
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

        $totalJumlahBarang = $fakturs->sum('total_barang');
        $totalHargaKeseluruhan = $fakturs->sum('total');

        $tanggalMulai = $fakturs->isNotEmpty() ? Carbon::parse($fakturs->first()->tgl_jual)->translatedFormat('d M Y') : 'N/A';
        $tanggalSelesai = $fakturs->isNotEmpty() ? Carbon::parse($fakturs->last()->tgl_jual)->translatedFormat('d M Y') : 'N/A';
        $rentangTanggal = ($tanggalMulai == $tanggalSelesai) ? $tanggalMulai : $tanggalMulai . ' - ' . $tanggalSelesai;

        $pdf = \PDF::loadView('pages.transaksi-faktur.print-kesimpulan', compact(
            'fakturs',
            'totalJumlahBarang',
            'totalHargaKeseluruhan',
            'rentangTanggal'
        ));
        
        return $pdf->stream('kesimpulan-faktur-' . time() . '.pdf');
    }
}
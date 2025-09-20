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
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use App\Services\SettingsService; // Untuk mengambil setting

class TransaksiOutletController extends Controller
{
    public function index()
    {
        $allgudangs = Gudang::all();
        return view('pages.transaksi-jual-outlet.index', compact('allgudangs'));
    }

    public function getData(Request $request)
    {
        if ($request->ajax()) {
            $barangs = Barang::join('t_jual_outlet', 't_barang.lok_spk', '=', 't_jual_outlet.lok_spk') // Join t_barang dengan t_jual_outlet
                ->join('t_faktur_outlet', 't_jual_outlet.nomor_faktur', '=', 't_faktur_outlet.nomor_faktur') // Join t_jual_outlet dengan t_faktur_outlet
                ->select(
                    't_barang.lok_spk',
                    't_barang.tipe',
                    't_faktur_outlet.nomor_faktur as nomor_faktur', // Mengambil nomor_faktur dari t_faktur_outlet
                    't_jual_outlet.harga as harga_jual', // Mengambil harga dari t_jual_outlet
                    't_barang.status_barang',
                    't_faktur_outlet.pembeli as pembeli_faktur', // Alias pembeli dari t_faktur_outlet
                    't_faktur_outlet.tgl_jual',
                    't_faktur_outlet.petugas as petugas_faktur'  // Alias petugas dari t_faktur_outlet
                )
                ->whereIn('t_barang.status_barang', [2, 5])
                ->orderBy('t_faktur_outlet.tgl_jual', 'desc');

            return DataTables::of($barangs)
                ->addColumn('harga_jual', function ($barang) {
                    return 'Rp. ' . number_format($barang->harga_jual, 0, ',', '.'); // Format harga ke Rupiah
                })
                ->addColumn('nomor_faktur', function ($barang) {
                    $url = route('transaksi-faktur-outlet.show', $barang->nomor_faktur);
                    return '<a href="' . $url . '" class="btn btn-info btn-sm" target="_blank">' . $barang->nomor_faktur . '</a>';
                })  
                ->filterColumn('pembeli', function ($query, $keyword) {
                    $query->where('t_faktur_outlet.pembeli', 'like', "%{$keyword}%");
                })
                ->filterColumn('petugas', function ($query, $keyword) {
                    $query->where('t_faktur_outlet.petugas', 'like', "%{$keyword}%");
                })
                ->filterColumn('nomor_faktur', function ($query, $keyword) {
                    $query->where('t_faktur_outlet.nomor_faktur', 'like', "%{$keyword}%");
                })
                ->rawColumns(['nomor_faktur'])
                ->make(true);
        }
    }   

    public function create(SettingsService $settingsService)
    {   
        // 1. Ambil setting waktu tutup. 
        //    Kita set default '23:59' (selalu buka) jika setting tidak ada.
        $waktuTutupString = $settingsService->get('WAKTU_TUTUP_OUTLET', '23:59');

        // 2. Parse string waktu (misal "17:00") ke objek Carbon.
        //    Carbon::parse() akan otomatis menggunakan tanggal hari ini.
        $waktuTutup = Carbon::parse($waktuTutupString);
        $waktuSekarang = Carbon::now();

        // 3. Cek apakah waktu sekarang SUDAH LEWAT atau SAMA DENGAN waktu tutup
        //    gte() = Greater Than or Equal (Lebih besar atau sama dengan)
        if ($waktuSekarang->gte($waktuTutup)) {
            
            // Jika sudah tutup, arahkan ke view 'transaksi-tutup'
            return view('pages.transaksi.transaksi-tutup', [
                'waktuTutup' => $waktuTutup->format('H:i') // Kirim data jam (misal "17:00")
            ]);
        }

        $gudangId = optional(Auth::user())->gudang_id;

        return view('pages.transaksi-jual-outlet.create',compact('gudangId'));
    }

    public function store(Request $request)
    {
        // TAHAP 1: VALIDASI INPUT & PRASYARAT AWAL
        $request->validate([
            'filedata' => 'required|file|mimes:xlsx,xls',
            'tgl_jual' => 'required|date',
            'nomor_faktur' => 'required|string',
            'pembeli' => 'required|string',
            'petugas' => 'required|string',
            'grade' => 'required|string',
            'foto' => 'nullable|image',
            'nominal' => 'nullable|numeric',
        ]);

        // Cek duplikat nomor faktur di database (Fail-Fast)
        $existingFaktur = FakturOutlet::where('nomor_faktur', $request->input('nomor_faktur'))->exists();
        if ($existingFaktur) {
            return redirect()->back()
                ->with('error', 'Gagal disimpan: Nomor Faktur sudah ada. Harap diganti!')
                ->withInput();
        }

        // Inisialisasi variabel
        $errors = [];
        $totalHargaJual = 0;
        $dataToProcess = [];
        $processedLokSpkInFile = [];

        // Ambil gudang_id dari user yang sedang login
        $gudangId = optional(Auth::user())->gudang_id;

        if (!$gudangId) {
            return redirect()->back()->with('error', 'Gagal memvalidasi data. User tidak terasosiasi dengan gudang manapun.');
        }

        // TAHAP 2: VALIDASI KESELURUHAN ISI FILE EXCEL (TANPA MENYIMPAN KE DB)
        $file = $request->file('filedata');
        $data = Excel::toArray([], $file);

        foreach ($data[0] as $index => $row) {
            if ($index === 0) continue; // Lewati header

            if (!isset($row[0]) || !isset($row[1])) {
                $errors[] = "Baris " . ($index + 1) . ": Data tidak lengkap (Lok SPK atau harga jual kosong).";
                continue;
            }

            $lokSpk = $row[0];
            $hargaJual = $row[1] * 1000;

            // Cek duplikat Lok SPK di dalam file yang sama
            if (in_array($lokSpk, $processedLokSpkInFile)) {
                $errors[] = "Baris " . ($index + 1) . ": Lok SPK '$lokSpk' duplikat di dalam file Excel.";
                continue;
            }
            $processedLokSpkInFile[] = $lokSpk;

            // Cari barang di database
            $barang = Barang::where('lok_spk', $lokSpk)->first();

            if ($barang) {
                if ($barang->gudang_id != $gudangId) {
                    $errors[] = "Baris " . ($index + 1) . ": Lok SPK '$lokSpk' tidak terdaftar di gudang Anda.";
                } elseif ($barang->status_barang != 1) {
                    $errors[] = "Baris " . ($index + 1) . ": Lok SPK '$lokSpk' sudah terjual atau statusnya tidak valid (" . $barang->status_barang . ").";
                } else {
                    $totalHargaJual += $hargaJual;
                    $dataToProcess[] = [
                        'lok_spk' => $lokSpk,
                        'harga_jual' => $hargaJual,
                    ];
                }
            } else {
                $errors[] = "Baris " . ($index + 1) . ": Lok SPK '$lokSpk' tidak ditemukan di database.";
            }
        }

        // TAHAP 3: GERBANG KEPUTUSAN (ALL OR NOTHING)
        // Jika ada satu saja error yang terkumpul, hentikan semua proses dan kembali dengan error.
        if (!empty($errors)) {
            return redirect()->back()->with('errors', $errors)->withInput();
        }

        // Pastikan ada data untuk diproses (menangani kasus file hanya berisi header atau baris invalid)
        if (empty($dataToProcess)) {
            return redirect()->back()
                ->with('error', 'Gagal: Tidak ada data valid yang dapat diproses dari file yang diunggah.')
                ->withInput();
        }

        // TAHAP 4: EKSEKUSI PENYIMPANAN (HANYA JIKA SEMUA DATA SEMPURNA)
        DB::beginTransaction();
        try {
            $newFaktur = FakturOutlet::create([
                'nomor_faktur' => $request->input('nomor_faktur'),
                'pembeli' => $request->input('pembeli'),
                'tgl_jual' => $request->input('tgl_jual'),
                'petugas' => $request->input('petugas'),
                'grade' => $request->input('grade'),
                'keterangan' => $request->input('keterangan'),
                'total' => $totalHargaJual,
            ]);

            foreach ($dataToProcess as $item) {
                $tipe = Barang::where('lok_spk', $item['lok_spk'])->value('tipe');
                $grade = $request->input('grade');

                $negoan = Negoan::where('tipe', $tipe)
                    ->where('grade', $grade)
                    ->where('status', 1)
                    ->orderBy('updated_at', 'desc')
                    ->first();

                Barang::where('lok_spk', $item['lok_spk'])->update([
                    'no_faktur' => $request->input('nomor_faktur'),
                    'harga_jual' => $item['harga_jual'],
                    'status_barang' => 5,
                ]);

                TransaksiJualOutlet::create([
                    'lok_spk' => $item['lok_spk'],
                    'nomor_faktur' => $request->input('nomor_faktur'),
                    'harga' => $item['harga_jual'],
                    'harga_acc' => $negoan->harga_acc ?? 0,
                ]);
            }

            // Proses bukti pembayaran jika ada
            if ($request->hasFile('foto') && $request->filled('nominal')) {
                $path = $request->file('foto')->store('faktur_bukti', 'public');

                FakturBuktiOutlet::create([
                    't_faktur_id' => $newFaktur->id,
                    'nominal' => $request->input('nominal'),
                    'foto' => $path
                ]);

                $totalNominal = FakturBuktiOutlet::where('t_faktur_id', $newFaktur->id)->sum('nominal');

                // Update status lunas pada objek faktur yang sama
                $newFaktur->is_lunas = ($totalNominal >= $newFaktur->total) ? 1 : 0;
                $newFaktur->save();
            }

            DB::commit(); // Semua query berhasil, simpan permanen perubahan.

            return redirect()->route('transaksi-faktur-outlet.show', ['nomor_faktur' => $request->input('nomor_faktur')])
                ->with('success', 'FakturOutlet berhasil disimpan. ' . count($dataToProcess) . ' barang diproses.');

        } catch (\Exception $e) {
            DB::rollBack(); // Terjadi kesalahan saat menyimpan, batalkan semua query yang sudah jalan.

            // Catat error $e->getMessage() ke log Anda untuk proses debug.
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan pada server saat menyimpan data. Semua perubahan telah dibatalkan.')
                ->withInput();
        }
    }

    public function addbarang(Request $request)
    {
        $validated = $request->validate(['lok_spk' => 'required|string', 'harga' => 'required|numeric|min:0', 'nomor_faktur' => 'required|exists:t_faktur_outlet,nomor_faktur']);
        try {
            DB::transaction(function () use ($validated) {
                $barang = Barang::where('lok_spk', $validated['lok_spk'])->firstOrFail();
                if ($barang->status_barang != 1) throw new \Exception("LOK SPK '{$validated['lok_spk']}' sudah terjual.");
                if (TransaksiJualOutlet::where('lok_spk', $validated['lok_spk'])->where('nomor_faktur', $validated['nomor_faktur'])->exists()) throw new \Exception("LOK SPK '{$validated['lok_spk']}' sudah ada di faktur ini.");
                
                $faktur = FakturOutlet::where('nomor_faktur', $validated['nomor_faktur'])->first();
                $hargaJual = $validated['harga'] * 1000;
                
                TransaksiJualOutlet::create(['lok_spk' => $validated['lok_spk'], 'nomor_faktur' => $validated['nomor_faktur'], 'harga' => $hargaJual, 'harga_acc' => 0]);
                $barang->update(['status_barang' => 2, 'no_faktur' => $validated['nomor_faktur'], 'harga_jual' => $hargaJual]);
                HistoryEditFakturOutlet::create(['faktur_id' => $faktur->id, 'update' => "Menambah barang (LOK SPK: {$validated['lok_spk']}) harga " . number_format($hargaJual), 'user_id' => auth()->id()]);
                $faktur->total += $hargaJual;
                $faktur->save();
            });
            return redirect()->back()->with('success', 'Barang berhasil ditambahkan.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal: ' . $e->getMessage())->withInput();
        }
    }

    public function update(Request $request, $id)
    {
        // 1. Validasi input dari form
        $validated = $request->validate([
            'lok_spk' => 'required|string|exists:t_barang,lok_spk',
            'harga' => 'required|numeric|min:0',
        ]);
    
        try {
            DB::transaction(function () use ($request, $id, $validated) {
                // Ambil data transaksi yang akan diubah beserta relasi fakturnya
                $transaksi_jual = TransaksiJualOutlet::with('faktur')->findOrFail($id);
    
                // Siapkan variabel-variabel yang dibutuhkan
                $lok_spk_lama = $transaksi_jual->lok_spk;
                $nomor_faktur = $transaksi_jual->nomor_faktur;
                $lok_spk_baru = $validated['lok_spk'];
                $harga_lama = $transaksi_jual->harga;
                $harga_baru = $validated['harga'] * 1000; // Harga dikalikan 1000
                $historyMessage = '';
    
                // Cek apakah LOK SPK diubah
                if ($lok_spk_lama !== $lok_spk_baru) {
                    // Skenario 1: Ganti Barang (LOK SPK berubah)
                    $barang_baru = Barang::where('lok_spk', $lok_spk_baru)->first();
                    if ($barang_baru->status_barang != 1) { // Pastikan barang baru tersedia
                        throw ValidationException::withMessages(['lok_spk' => 'LOK SPK baru sudah terjual atau statusnya tidak valid.']);
                    }
    
                    // Kembalikan status barang LAMA menjadi tersedia
                    Barang::where('lok_spk', $lok_spk_lama)->update(['status_barang' => 1, 'harga_jual' => null, 'no_faktur' => null]);
                    
                    // Ubah status barang BARU menjadi terjual
                    $barang_baru->update(['status_barang' => 2, 'harga_jual' => $harga_baru, 'no_faktur' => $nomor_faktur]);
                    
                    $historyMessage = "Ganti barang dari '{$lok_spk_lama}' ke '{$lok_spk_baru}' dengan harga Rp " . number_format($harga_baru);
                } else {
                    // Skenario 2: Hanya Harga yang Berubah
                    $transaksi_jual->barang()->update(['harga_jual' => $harga_baru]);
                    $historyMessage = "Ubah harga untuk '{$lok_spk_lama}' dari Rp " . number_format($harga_lama) . " menjadi Rp " . number_format($harga_baru);
                }
    
                // Update record di t_jual_outlet
                $transaksi_jual->update(['lok_spk' => $lok_spk_baru, 'harga' => $harga_baru]);
    
                // Catat ke riwayat
                HistoryEditFakturOutlet::create([
                    'faktur_id' => $transaksi_jual->faktur->id,
                    'update'    => $historyMessage,
                    'user_id'   => auth()->id(),
                ]);
    
                // Hitung ulang total faktur
                $totalBaru = TransaksiJualOutlet::where('nomor_faktur', $nomor_faktur)->sum('harga');
                FakturOutlet::where('nomor_faktur', $nomor_faktur)->update(['total' => $totalBaru]);
            });
    
            return redirect()->back()->with('success', 'Transaksi berhasil diupdate!');
    
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            DB::transaction(function () use ($id) {
                $transaksi = TransaksiJualOutlet::with('faktur')->findOrFail($id);
                HistoryEditFakturOutlet::create(['faktur_id' => $transaksi->faktur->id, 'update' => "Menghapus barang (LOK SPK: {$transaksi->lok_spk})", 'user_id' => auth()->id()]);
                Barang::where('lok_spk', $transaksi->lok_spk)->update(['status_barang' => 1, 'no_faktur' => null, 'harga_jual' => null]);
                $nomorFaktur = $transaksi->nomor_faktur;
                $transaksi->delete();
                $totalBaru = TransaksiJualOutlet::where('nomor_faktur', $nomorFaktur)->sum('harga');
                FakturOutlet::where('nomor_faktur', $nomorFaktur)->update(['total' => $totalBaru]);
            });
            return redirect()->back()->with('success', 'Barang berhasil dihapus.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }
    
    public function getSuggestNoFak(Request $request)
    {
        $kodeFaktur = $request->kode_faktur;
        $tglJual = $request->tgl_jual ? Carbon::parse($request->tgl_jual) : Carbon::now(); 
        $currentMonthYear = $tglJual->format('my'); // Menggunakan tanggal yang dipilih user

        // Ambil faktur terakhir dengan format yang sesuai
        $lastFaktur = FakturOutlet::where('nomor_faktur', 'like', "$kodeFaktur-$currentMonthYear-%")
            ->orderByRaw("CAST(SUBSTRING_INDEX(nomor_faktur, '-', -1) AS UNSIGNED) DESC")
            ->first();

        // Tentukan nomor urut
        if ($lastFaktur) {
            preg_match('/-(\d+)$/', $lastFaktur->nomor_faktur, $matches);
            $lastNumber = isset($matches[1]) ? (int) $matches[1] : 0;
            $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '001';
        }

        // Format nomor faktur baru
        $suggestedNoFak = "$kodeFaktur-$currentMonthYear-$newNumber";

        return response()->json(['suggested_no_fak' => $suggestedNoFak]);
    }

}

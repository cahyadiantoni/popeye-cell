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
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;

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

    public function create()
    {   
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

    public function destroy($id)
    {
        try {
            $transaksi = TransaksiJualOutlet::where('id', $id)->firstOrFail();

            // Mendapatkan lok_spk dari transaksi
            $lok_spk = $transaksi->lok_spk;

            // Melakukan update pada tabel Barang
            Barang::where('lok_spk', $lok_spk)->update([
                'status_barang' => 1,
                'no_faktur' => null,
                'harga_jual' => 0, 
            ]);

            // Hapus Transaksi
            $nomorFaktur = $transaksi->nomor_faktur;
            $transaksi->delete();

            // Hitung ulang total pada FakturOutlet
            $totalBaru = TransaksiJualOutlet::where('nomor_faktur', $nomorFaktur)->sum('harga');
            FakturOutlet::where('nomor_faktur', $nomorFaktur)->update(['total' => $totalBaru]);

            return redirect()->back()->with('success', 'Barang berhasil dihapus');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }


    public function update(Request $request)
    {
        try {
            $validated = $request->validate([
                'id' => 'required|exists:t_jual_outlet,id',
                'lok_spk' => 'required|exists:t_jual_outlet,lok_spk',
                'harga' => 'required|numeric|min:0',
            ]);
    
            // Gunakan firstOrFail() untuk pencarian berdasarkan 'id'
            $transaksi = TransaksiJualOutlet::where('id', $validated['id'])->firstOrFail();
            $transaksi->update(['harga' => $validated['harga']]);
    
            // Update harga_jual pada model Barang
            $barang = $transaksi->barang;
            $barang->update(['harga_jual' => $validated['harga']]);
    
            // Hitung ulang total pada FakturOutlet
            $nomorFaktur = $transaksi->nomor_faktur;
            $totalBaru = TransaksiJualOutlet::where('nomor_faktur', $nomorFaktur)->sum('harga');
            FakturOutlet::where('nomor_faktur', $nomorFaktur)->update(['total' => $totalBaru]);
    
            return redirect()->back()->with('success', 'Harga berhasil diupdate');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
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

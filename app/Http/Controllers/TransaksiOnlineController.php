<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\Gudang;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Kirim;
use App\Models\FakturOnline;
use App\Models\TransaksiJualOnline;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;

class TransaksiOnlineController extends Controller
{
    public function index()
    {
        $allgudangs = Gudang::all();
        return view('pages.transaksi-jual-online.index', compact('allgudangs'));
    }

    public function getData(Request $request)
    {
        if ($request->ajax()) {
            $barangs = Barang::join('t_jual_online', 't_barang.lok_spk', '=', 't_jual_online.lok_spk')
                ->join('t_faktur_online', 't_jual_online.faktur_online_id', '=', 't_faktur_online.id')
                ->select(
                    't_barang.lok_spk',
                    't_barang.tipe',
                    't_jual_online.harga as harga_jual', // Gunakan harga dari t_jual_online
                    't_barang.status_barang',
                    't_faktur_online.id as id_faktur_online',
                    't_faktur_online.title as title_faktur',
                    't_jual_online.invoice as invoice',
                    't_faktur_online.toko as toko_faktur',
                    't_faktur_online.tgl_jual',
                    't_faktur_online.petugas as petugas_faktur'
                )
                ->whereIn('t_barang.status_barang', [2, 5])
                ->orderBy('t_faktur_online.tgl_jual', 'desc');
    
            return DataTables::of($barangs)
                ->addColumn('harga_jual', function ($barang) {
                    return 'Rp. ' . number_format($barang->harga_jual, 0, ',', '.');
                })
                ->addColumn('title_faktur', function ($barang) {
                    $url = route('transaksi-faktur-online.show', $barang->id_faktur_online);
                    return '<a href="' . $url . '" class="btn btn-info btn-sm" target="_blank" rel="noopener noreferrer">' . $barang->title_faktur . '</a>';
                })
                ->filterColumn('invoice', function ($query, $keyword) {
                    $query->where('t_jual_online.invoice', 'like', "%{$keyword}%");
                })
                ->filterColumn('title', function ($query, $keyword) {
                    $query->where('t_faktur_online.title', 'like', "%{$keyword}%");
                })
                ->filterColumn('toko', function ($query, $keyword) {
                    $query->where('t_faktur_online.toko', 'like', "%{$keyword}%");
                })
                ->filterColumn('petugas', function ($query, $keyword) {
                    $query->where('t_faktur_online.petugas', 'like', "%{$keyword}%");
                })
                ->rawColumns(['title_faktur'])
                ->make(true);
        }
    }    

    public function create()
    {
        return view('pages.transaksi-jual-online.create');
    }

    public function store(Request $request)
    {
        // TAHAP 1: VALIDASI INPUT & PRASYARAT AWAL
        $request->validate([
            'filedata' => 'required|file|mimes:xlsx,xls',
            'tgl_jual' => 'required|date',
            'title' => 'required|string',
            'toko' => 'required|string',
            'petugas' => 'required|string',
            'grade' => 'required|string',
        ]);

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

        // 2. Baca dan Validasi Seluruh Isi File Excel (Tanpa Menyimpan ke DB)
        $file = $request->file('filedata');
        $data = Excel::toArray([], $file);

        foreach ($data[0] as $index => $row) {
            // Lewati baris header
            if ($index === 0) continue;

            // Validasi kelengkapan kolom di Excel
            if (!isset($row[0], $row[1], $row[2], $row[3])) {
                $errors[] = "Baris " . ($index + 1) . ": Data tidak lengkap. Pastikan kolom Invoice, Lok SPK, Harga Jual, dan PJ terisi.";
                continue; // Lanjut ke baris berikutnya
            }
            
            $invoice = $row[0];
            $lokSpk = $row[1];
            $hargaJual = $row[2] * 1000;
            $pj = $row[3] * 1000;

            // Cek duplikat lok_spk di dalam file Excel
            if (in_array($lokSpk, $processedLokSpkInFile)) {
                $errors[] = "Baris " . ($index + 1) . ": Lok SPK '$lokSpk' duplikat di dalam file Excel.";
                continue;
            }
            $processedLokSpkInFile[] = $lokSpk;

            // Cari barang berdasarkan lok_spk
            $barang = Barang::where('lok_spk', $lokSpk)->first();

            if ($barang) {
                if ($barang->gudang_id != $gudangId) {
                    $errors[] = "Baris " . ($index + 1) . ": Lok SPK '$lokSpk' tidak terdaftar di gudang Anda.";
                } elseif ($barang->status_barang != 1) {
                    $errors[] = "Baris " . ($index + 1) . ": Lok SPK '$lokSpk' sudah terjual atau statusnya tidak valid (" . $barang->status_barang . ").";
                } else {
                    $totalHargaJual += $hargaJual;
                    $dataToProcess[] = [
                        'invoice' => $invoice,
                        'lok_spk' => $lokSpk,
                        'harga_jual' => $hargaJual,
                        'pj' => $pj,
                    ];
                }
            } else {
                $errors[] = "Baris " . ($index + 1) . ": Lok SPK '$lokSpk' tidak ditemukan di database.";
            }
        }

        // 3. TAHAP KRITIS: Gerbang Keputusan
        // Jika ada satu saja error yang terkumpul, batalkan semua proses.
        if (!empty($errors)) {
            return redirect()->back()->with('errors', $errors)->withInput();
        }

        // 4. Pastikan ada data untuk diproses (menangani file kosong atau tanpa baris valid)
        if (empty($dataToProcess)) {
            return redirect()->back()
                ->with('error', 'Gagal: Tidak ada data valid yang dapat diproses dari file yang diunggah.')
                ->withInput();
        }

        // 5. JIKA LOLOS SEMUA VALIDASI: Lakukan Operasi Database dengan Transaksi
        DB::beginTransaction();
        try {
            $fakturOnline = FakturOnline::create([
                'title' => $request->input('title'),
                'toko' => $request->input('toko'),
                'tgl_jual' => $request->input('tgl_jual'),
                'petugas' => $request->input('petugas'),
                'grade' => $request->input('grade'),
                'keterangan' => $request->input('keterangan'),
                'total' => $totalHargaJual,
            ]);

            $fakturOnlineId = $fakturOnline->id;

            // Lakukan create/update untuk setiap data yang sudah divalidasi
            foreach ($dataToProcess as $item) {
                Barang::where('lok_spk', $item['lok_spk'])->update([
                    'no_faktur' => $fakturOnlineId,
                    'harga_jual' => $item['harga_jual'],
                    'status_barang' => 5,
                ]);

                TransaksiJualOnline::create([
                    'invoice' => $item['invoice'],
                    'lok_spk' => $item['lok_spk'],
                    'faktur_online_id' => $fakturOnlineId,
                    'harga' => $item['harga_jual'],
                    'pj' => $item['pj'],
                ]);
            }

            DB::commit(); // Konfirmasi dan simpan semua perubahan ke database

            return redirect()->route('transaksi-faktur-online.show', ['nomor_faktur' => $fakturOnlineId])
                ->with('success', 'Faktur berhasil disimpan. ' . count($dataToProcess) . ' barang berhasil diproses.');

        } catch (\Exception $e) {
            DB::rollBack(); // Jika terjadi error saat proses penyimpanan, batalkan semua yang sudah di-query

            // Opsional: catat error $e->getMessage() ke log untuk debugging
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan pada server saat menyimpan data. Semua perubahan telah dibatalkan.')
                ->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $transaksi = TransaksiJualOnline::where('id', $id)->firstOrFail();

            // Mendapatkan lok_spk dari transaksi
            $lok_spk = $transaksi->lok_spk;

            Barang::where('lok_spk', $lok_spk)->update([
                'status_barang' => 1,
                'no_faktur' => null,
                'harga_jual' => 0, 
            ]);

            // Hapus Transaksi
            $nomorFaktur = $transaksi->faktur_online_id;
            $transaksi->delete();

            // Hitung ulang total pada Faktur
            $totalBaru = TransaksiJualOnline::where('faktur_online_id', $nomorFaktur)->sum('harga');
            FakturOnline::where('id', $nomorFaktur)->update(['total' => $totalBaru]);

            return redirect()->back()->with('success', 'Barang berhasil dihapus');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }


    public function update(Request $request)
    {
        try {
            $validated = $request->validate([
                'invoice' => 'required',
                'id' => 'required|exists:t_jual_online,id',
                'lok_spk' => 'required|exists:t_jual_online,lok_spk',
                'harga' => 'required|numeric|min:0',
                'pj' => 'required|numeric|min:0',
            ]);
    
            // Gunakan firstOrFail() untuk pencarian berdasarkan 'id'
            $transaksi = TransaksiJualOnline::where('id', $validated['id'])->firstOrFail();

            // Perbarui data dengan kolom tambahan
            $transaksi->update([
                'harga' => $validated['harga'],
                'pj' => $validated['pj'], // Update kolom pj
                'invoice' => $validated['invoice'], // Update kolom invoice
            ]);
    
            // Update harga_jual pada model Barang
            $barang = $transaksi->barang;
            $barang->update(['harga_jual' => $validated['harga']]);
    
            // Hitung ulang total pada Faktur
            $nomorFaktur = $transaksi->faktur_online_id;
            $totalBaru = TransaksiJualOnline::where('faktur_online_id', $nomorFaktur)->sum('harga');
            FakturOnline::where('id', $nomorFaktur)->update(['total' => $totalBaru]);
    
            return redirect()->back()->with('success', 'Data berhasil diupdate');
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
        $lastFaktur = FakturOnline::where('title', 'like', "$kodeFaktur-$currentMonthYear-%")
            ->orderByRaw("CAST(SUBSTRING(title, 10, LENGTH(title) - 9) AS UNSIGNED) DESC")
            ->first();

        // Tentukan nomor urut
        if ($lastFaktur) {
            preg_match('/-(\d+)$/', $lastFaktur->title, $matches);
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

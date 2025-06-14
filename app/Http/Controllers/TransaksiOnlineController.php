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
        $validLokSpk = [];
        $fakturOnlineId = 0;
        $processedLokSpk = []; // Untuk memeriksa duplikat di dalam Excel

        // Membaca file Excel
        $file = $request->file('filedata');
        $data = Excel::toArray([], $file);

        foreach ($data[0] as $index => $row) {
            // Lewati baris pertama jika merupakan header
            if ($index === 0) continue;

            // Validasi kolom di Excel
            if (isset($row[0]) && isset($row[1]) && isset($row[2]) && isset($row[3])) {
                $invoice = $row[0]; // Invoice
                $lokSpk = $row[1]; // Lok SPK
                $hargaJual = $row[2] * 1000; // Harga Jual
                $pj = $row[3] * 1000; // Harga PJ

                // Cek duplikat lok_spk di dalam Excel
                if (in_array($lokSpk, $processedLokSpk)) {
                    $errors[] = "Row " . ($index + 1) . ": Lok SPK '$lokSpk' duplikat di dalam file Excel.";
                    continue;
                }

                // Tambahkan lok_spk ke daftar yang sudah diproses
                $processedLokSpk[] = $lokSpk;

                // Cari barang berdasarkan lok_spk
                $barang = Barang::where('lok_spk', $lokSpk)->first();

                if ($barang) {
                    // Cek apakah status_barang adalah 0 atau 1
                    if (in_array($barang->status_barang, [0, 1])) {
                        // Tambahkan harga_jual ke total
                        $totalHargaJual += $hargaJual;

                        // Simpan lok_spk untuk update nanti
                        $validLokSpk[] = [
                            'invoice' => $invoice,
                            'lok_spk' => $lokSpk,
                            'harga_jual' => $hargaJual,
                            'pj' => $pj,
                        ];
                    } else {
                        $errors[] = "Row " . ($index + 1) . ": Lok SPK '$lokSpk' memiliki status_barang yang tidak sesuai.";
                    }
                } else {
                    $errors[] = "Row " . ($index + 1) . ": Lok SPK '$lokSpk' tidak ditemukan.";
                }
            } else {
                $errors[] = "Row " . ($index + 1) . ": Data tidak valid (Lok SPK atau harga jual kosong).";
            }
        }

        // Simpan data Faktur jika ada data valid
        if (!empty($validLokSpk)) {
            $fakturOnline = FakturOnline::create([
                'title' => $request->input('title'),
                'toko' => $request->input('toko'),
                'tgl_jual' => $request->input('tgl_jual'),
                'petugas' => $request->input('petugas'),
                'grade' => $request->input('grade'),
                'keterangan' => $request->input('keterangan'),
                'total' => $totalHargaJual,
            ]);

            // Ambil ID dari data yang baru saja dibuat
            $fakturOnlineId = $fakturOnline->id;

            // Update Barang untuk lok_spk yang valid
            foreach ($validLokSpk as $item) {
                Barang::where('lok_spk', $item['lok_spk'])->update([
                    'no_faktur' => $fakturOnlineId,
                    'harga_jual' => $item['harga_jual'], // Update harga_jual dari Excel
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

            // Tampilkan pesan sukses dan error
            return redirect()->route('transaksi-faktur-online.show', ['nomor_faktur' => $fakturOnlineId])
                ->with('success', 'Faktur berhasil disimpan. ' . count($validLokSpk) . ' barang diproses.')
                ->with('errors', $errors);
        }

        // Jika tidak ada data valid, hanya tampilkan error
        return redirect()->back()->with('errors', $errors);
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

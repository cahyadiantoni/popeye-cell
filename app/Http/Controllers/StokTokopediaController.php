<?php

// app/Http/Controllers/StokTokopediaController.php
namespace App\Http\Controllers;

use App\Models\TokopediaBarangMasuk;
use App\Models\TokopediaBarangKeluar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StokTokopediaController extends Controller
{
    public function index(Request $request)
    {
        // Filter opsional
        $start = $request->query('start_date');
        $end   = $request->query('end_date');
        $qname = trim((string)$request->query('search', ''));

        // Ambil daftar NAMA BARANG (gabungan dari masuk & keluar) + cari
        $namesIn = TokopediaBarangMasuk::select('nama_barang')
            ->when($start, fn($q)=>$q->where('tgl_beli','>=',$start))
            ->when($end, fn($q)=>$q->where('tgl_beli','<=',$end))
            ->when($qname, fn($q)=>$q->where('nama_barang','like','%'.strtoupper($qname).'%'));

        $names = TokopediaBarangKeluar::select('nama_barang')
            ->when($start, fn($q)=>$q->where('tgl_keluar','>=',$start))
            ->when($end, fn($q)=>$q->where('tgl_keluar','<=',$end))
            ->when($qname, fn($q)=>$q->where('nama_barang','like','%'.strtoupper($qname).'%'))
            ->union($namesIn)
            ->distinct()
            ->orderBy('nama_barang')
            ->pluck('nama_barang');

        // Hitung total IN per nama
        $totalIn = TokopediaBarangMasuk::select('nama_barang', DB::raw('SUM(quantity) as qty'))
            ->when($start, fn($q)=>$q->where('tgl_beli','>=',$start))
            ->when($end, fn($q)=>$q->where('tgl_beli','<=',$end))
            ->groupBy('nama_barang')
            ->pluck('qty','nama_barang');

        // Hitung total OUT per nama
        $totalOut = TokopediaBarangKeluar::select('nama_barang', DB::raw('SUM(quantity) as qty'))
            ->when($start, fn($q)=>$q->where('tgl_keluar','>=',$start))
            ->when($end, fn($q)=>$q->where('tgl_keluar','<=',$end))
            ->groupBy('nama_barang')
            ->pluck('qty','nama_barang');

        // Ambil history per nama (gabungan IN/OUT) — batasi 50 entri per barang untuk tampilan
        $data = [];
        foreach ($names as $nama) {
            // History IN
            $histIn = TokopediaBarangMasuk::selectRaw("
                        'IN'  as tipe,
                        tgl_beli   as tanggal,
                        quantity   as qty,
                        NULL       as kode_toko,
                        NULL       as nama_toko,
                        NULL       as nama_am
                    ")
                ->where('nama_barang',$nama)
                ->when($start, fn($q)=>$q->where('tgl_beli','>=',$start))
                ->when($end, fn($q)=>$q->where('tgl_beli','<=',$end));

            // History OUT
            $histOut = TokopediaBarangKeluar::select(
                        DB::raw("'OUT' as tipe"),
                        'tgl_keluar as tanggal',
                        'quantity as qty',
                        'kode_toko',
                        'nama_toko',
                        'nama_am'
                    )
                ->where('nama_barang',$nama)
                ->when($start, fn($q)=>$q->where('tgl_keluar','>=',$start))
                ->when($end, fn($q)=>$q->where('tgl_keluar','<=',$end));

            // Union + urut tanggal ASC (biar kronologis)
            $history = $histOut->unionAll($histIn)
                ->orderBy('tanggal','asc')
                ->get()
                ->take(50); // batasi agar halaman ringan

            $in  = (int)($totalIn[$nama]  ?? 0);
            $out = (int)($totalOut[$nama] ?? 0);
            $stok = $in - $out; // boleh negatif

            $data[] = [
                'nama_barang' => $nama,
                'history'     => $history,
                'stok'        => $stok,
            ];
        }

        // Opsional: pagination manual (sederhana)
        // Kamu bisa ganti ke server-side DataTables kalau list sangat besar.
        return view('pages.stok-tokopedia.index', [
            'rows'   => $data,
            'search' => $qname,
            'start'  => $start,
            'end'    => $end,
        ]);
    }
}

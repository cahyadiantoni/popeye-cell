<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HistoryEditBarang;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Carbon;

class RiwayatBarangController extends Controller
{
    /**
     * Menampilkan halaman riwayat perubahan data barang.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Cek jika request datang dari AJAX (DataTables)
        if ($request->ajax()) {
            // Buat query ke model HistoryEditBarang
            $query = HistoryEditBarang::query()
                // Eager load relasi 'user' untuk mengambil nama user, ini lebih efisien
                ->with('user') 
                // Urutkan berdasarkan 'created_at' dari yang terbaru ke terlama
                ->orderBy('created_at', 'desc'); 

            return DataTables::of($query)
                ->editColumn('created_at', function ($history) {
                    // Format tanggal dan waktu menjadi format yang mudah dibaca
                    return Carbon::parse($history->created_at)->translatedFormat('d F Y H:i:s');
                })
                ->addColumn('user.name', function ($history) {
                    // Ambil nama user dari relasi. Beri fallback jika user tidak ditemukan.
                    return $history->user->name ?? 'User Tidak Dikenal';
                })
                ->editColumn('update', function ($history) {
                    // Gunakan nl2br() untuk mengubah newline (\n) menjadi tag <br> di HTML
                    // Gunakan e() untuk escaping demi keamanan (mencegah XSS)
                    return nl2br(e($history->update));
                })
                // Beritahu DataTables bahwa kolom 'update' berisi HTML
                ->rawColumns(['update'])
                ->make(true);
        }

        // Jika bukan request AJAX, tampilkan view-nya
        return view('pages.riwayat-barang.index');
    }
}
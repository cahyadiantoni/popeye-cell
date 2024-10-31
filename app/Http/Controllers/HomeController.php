<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $stokGudangs = Barang::selectRaw('gudang_id, COUNT(*) as total')
            ->whereIn('status_barang', [0, 1]) // Ambil status 0 dan 1
            ->groupBy('gudang_id') // Kelompokkan berdasarkan gudang_id
            ->get()
            ->keyBy('gudang_id'); // Mempermudah akses data berdasarkan gudang_id

        return view('pages.dashboard', compact('stokGudangs'));
    }

}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HistoryEditFakturAtas; 
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Carbon;

class HistoryEditFakturAtasController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = HistoryEditFakturAtas::with(['user', 'faktur'])->orderBy('created_at', 'desc');

            return DataTables::of($query)
                ->editColumn('created_at', fn($history) => Carbon::parse($history->created_at)->translatedFormat('d F Y H:i:s'))
                ->addColumn('user.name', fn($history) => $history->user->name ?? 'User Tidak Dikenal')
                
                // --- UBAH BARIS DI BAWAH INI ---
                ->editColumn('update', fn($history) => $history->update)
                
                ->addColumn('faktur.nomor_faktur', function ($history) {
                    return $history->faktur->nomor_faktur ?? 'Faktur Tidak Ditemukan';
                })

                ->rawColumns(['update']) // Baris ini sudah benar, fungsinya untuk merender HTML
                ->make(true);
        }
        
        return view('pages.history-edit-faktur-atas.index');
    }
}
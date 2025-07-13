<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HistoryEditFakturBawah;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Carbon;

class HistoryEditFakturBawahController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            // DIUBAH: Query ke model HistoryEditFakturBawah
            $query = HistoryEditFakturBawah::with(['user', 'faktur'])->orderBy('created_at', 'desc');

            return DataTables::of($query)
                ->editColumn('created_at', fn($history) => Carbon::parse($history->created_at)->translatedFormat('d F Y H:i:s'))
                ->addColumn('user.name', fn($history) => $history->user->name ?? 'User Tidak Dikenal')
                
                // Kolom 'update' ini akan merender HTML karena ada di rawColumns
                ->editColumn('update', fn($history) => $history->update)
                
                ->addColumn('faktur.nomor_faktur', function ($history) {
                    return $history->faktur->nomor_faktur ?? 'Faktur Tidak Ditemukan';
                })

                ->rawColumns(['update'])
                ->make(true);
        }
        
        // DIUBAH: Mengarahkan ke view untuk Faktur Bawah
        return view('pages.history-edit-faktur-bawah.index');
    }
}
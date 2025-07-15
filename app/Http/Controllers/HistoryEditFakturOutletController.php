<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HistoryEditFakturOutlet;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Carbon;

class HistoryEditFakturOutletController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = HistoryEditFakturOutlet::with(['user', 'faktur'])->orderBy('created_at', 'desc');
            return DataTables::of($query)
                ->editColumn('created_at', fn($h) => Carbon::parse($h->created_at)->translatedFormat('d F Y H:i:s'))
                ->addColumn('user.name', fn($h) => $h->user->name ?? 'User Tidak Dikenal')
                ->editColumn('update', fn($h) => $h->update)
                ->addColumn('faktur.nomor_faktur', function ($h) {
                    return $h->faktur->nomor_faktur ?? 'Faktur Dihapus';
                })
                ->rawColumns(['update'])
                ->make(true);
        }
        return view('pages.history-edit-faktur-outlet.index');
    }
}
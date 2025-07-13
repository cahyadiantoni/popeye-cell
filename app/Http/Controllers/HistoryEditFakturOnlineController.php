<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HistoryEditFakturOnline;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Carbon;

class HistoryEditFakturOnlineController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = HistoryEditFakturOnline::with(['user', 'faktur'])->orderBy('created_at', 'desc');

            return DataTables::of($query)
                ->editColumn('created_at', fn($history) => Carbon::parse($history->created_at)->translatedFormat('d F Y H:i:s'))
                ->addColumn('user.name', fn($history) => $history->user->name ?? 'User Tidak Dikenal')
                ->editColumn('update', fn($history) => $history->update)
                ->addColumn('faktur.title', function ($history) {
                    return $history->faktur->title ?? 'Faktur Tidak Ditemukan';
                })
                ->rawColumns(['update'])
                ->make(true);
        }
        
        return view('pages.history-edit-faktur-online.index');
    }
}
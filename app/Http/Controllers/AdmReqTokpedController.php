<?php

namespace App\Http\Controllers;

use App\Exports\ReqTokpedExport;
use App\Models\AdmItemTokped;
use Illuminate\Http\Request;
use App\Models\AdmReqTokped;
use App\Models\AdmSetting;
use App\Models\AdmReqTokpedBukti;
use App\Models\AdmReqTokpedItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use App\Mail\ReqTokpedStatusUpdated;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TodoTransferExport;
use Illuminate\Support\Facades\Log;

class AdmReqTokpedController extends Controller
{
    // Menampilkan daftar To do Transfer
    public function index()
    {
        $roleUser = optional(Auth::user())->role;
        
        $query = AdmReqTokped::with('user')->orderBy('tgl', 'desc')->orderBy('status', 'asc');

        // Jika bukan admin, filter berdasarkan user_id
        if ($roleUser == 'admin') {
            $query->whereNotIn('status', [0, 2]);
        } else {
            $query->where('user_id', Auth::id());
        }

        $todos = $query->get();

        // Ambil nilai is_active dari AdmSetting dengan kode 'PTOKPED'
        $isActive = AdmSetting::where('kode', 'PTOKPED')->value('is_active');

        return view('pages.req-tokped.index', compact('todos', 'isActive', 'roleUser'));
    }

    public function create()
    {
        $isActive = AdmSetting::where('kode', 'PTOKPED')->value('is_active');

        if($isActive){
            return view('pages.req-tokped.create');
        }else{
            return redirect()->route('req-tokped.index')->with('error', 'Pengajuan Barang Tokopedia sedang ditutup.');
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'tgl' => 'required|date',
            'kode_lok' => 'required|string|max:255',
            'nama_toko' => 'required|string|max:255',
            'alasan' => 'nullable|string',
        ]);

        AdmReqTokped::create([
            'tgl' => $request->tgl,
            'kode_lok' => $request->kode_lok,
            'nama_toko' => $request->nama_toko,
            'user_id' => Auth::id(),
            'alasan' => $request->alasan,
            'status' => 0, // Default status
        ]);

        return redirect()->route('req-tokped.index')->with('success', 'Request Tokped berhasil dibuat.');
    }

    // Menampilkan detail To do Transfer
    public function show($id)
    {
        $todoTransfer = AdmReqTokped::with('bukti')->findOrFail($id);

        $roleUser = optional(Auth::user())->role;

        $items = AdmItemTokped::all();
        
        return view('pages.req-tokped.detail', compact('todoTransfer', 'roleUser', 'items'));
    }

    public function edit($id)
    {
        $todoTransfer = AdmReqTokped::findOrFail($id);
        return view('pages.req-tokped.edit', compact('todoTransfer'));
    }
    
    public function update(Request $request, $id)
    {
        $request->validate([
            'tgl' => 'required|date',
            'kode_lok' => 'required|string|max:255',
            'nama_toko' => 'required|string|max:255',
            'alasan' => 'nullable|string',
        ]);
    
        $todoTransfer = AdmReqTokped::findOrFail($id);
        $todoTransfer->update($request->all());
    
        return redirect()->route('req-tokped.show', $id)->with('success', 'Data berhasil diperbarui.');
    }

    public function updateStatus($id, $status)
    {
        $reqTokped = AdmReqTokped::with('user')->findOrFail($id);

        if (in_array($reqTokped->status, [3, 5])) {
            return back()->with('error', 'Data tidak dapat diubah.');
        }

        if ($reqTokped->status == 0 && $status != 1) {
            return back()->with('error', 'Status hanya bisa diubah ke "Kirim" terlebih dahulu.');
        }

        if ($reqTokped->status == 1 && !in_array($status, [2, 3, 4, 5])) {
            return back()->with('error', 'Status yang dipilih tidak valid.');
        }

        // Update status
        $reqTokped->update(['status' => $status]);

        // Kirim email hanya jika status berubah ke 2 (Revisi), 3 (Ditolak), atau 5 (Sudah Ditransfer)
        if (in_array($status, [2, 3, 5]) && $reqTokped->user && $reqTokped->user->email) {
            try {
                Mail::to($reqTokped->user->email)->send(new ReqTokpedStatusUpdated($reqTokped));
                Log::info("Email berhasil dikirim ke: " . $reqTokped->user->email);
            } catch (\Exception $e) {
                Log::error("Gagal mengirim email: " . $e->getMessage());
            }
        } else if($status == 1){
            try {
                Mail::to("adpusatindogadai@gmail.com")->send(new ReqTokpedStatusUpdated($reqTokped));
                Log::info("Email berhasil dikirim ke: " . "adpusatindogadai@gmail.com");
            } catch (\Exception $e) {
                Log::error("Gagal mengirim email: " . $e->getMessage());
            }
        }

        return back()->with('success', 'Status berhasil diperbarui.');
    }      

    // Menambahkan bukti transfer
    public function storeBukti(Request $request)
    {
        $request->validate([
            'adm_req_tokped_id' => 'required|exists:adm_req_tokped,id',
            'keterangan' => 'required|string|max:255',
            'foto' => 'required|image'
        ]);
    
        $path = $request->file('foto')->store('bukti_req_tokped', 'public');
    
        AdmReqTokpedBukti::create([
            'adm_req_tokped_id' => $request->adm_req_tokped_id,
            'keterangan' => $request->keterangan,
            'foto' => $path
        ]);
    
        return back()->with('success', 'Bukti transfer berhasil ditambahkan.');
    }    

    // Menghapus bukti transfer
    public function deleteBukti($id)
    {
        $bukti = AdmReqTokpedBukti::findOrFail($id);
        Storage::disk('public')->delete($bukti->foto);
        $bukti->delete();
    
        return back()->with('success', 'Bukti transfer berhasil dihapus.');
    }  

    public function storeItem(Request $request)
    {
        $request->validate([
            'adm_req_tokped_id' => 'required|exists:adm_req_tokped,id',
            'adm_item_tokped_id' => 'required|exists:adm_item_tokped,id',
            'nama_barang' => 'nullable',
            'quantity' => 'required',
        ]);
    
        AdmReqTokpedItem::create([
            'adm_req_tokped_id' => $request->adm_req_tokped_id,
            'adm_item_tokped_id' => $request->adm_item_tokped_id,
            'nama_barang' => $request->nama_barang,
            'quantity' => $request->quantity,
        ]);
    
        return back()->with('success', 'Item berhasil ditambahkan.');
    }    

    public function deleteItem($id)
    {
        $bukti = AdmReqTokpedItem::findOrFail($id);
        $bukti->delete();
    
        return back()->with('success', 'Item berhasil dihapus.');
    }  

    public function updateItem(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|integer',
        ]);

        $item = AdmReqTokpedItem::findOrFail($id);
        $item->update([
            'quantity' => $request->quantity,
        ]);

        return back()->with('success', 'Item berhasil diperbarui.');
    }
    
    public function export(Request $request)
    {
        $filters = [
            'status' => $request->query('status'),
            'start_date' => $request->query('start_date'),
            'end_date' => $request->query('end_date')
        ];
    
        return Excel::download(new ReqTokpedExport($filters), 'req_tokped.xlsx');
    }    
    
}

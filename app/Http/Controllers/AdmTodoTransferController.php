<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AdmTodoTf;
use App\Models\AdmSetting;
use App\Models\AdmTodoTfBukti;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use App\Mail\TodoTransferStatusUpdated;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TodoTransferExport;
use Illuminate\Support\Facades\Log;

class AdmTodoTransferController extends Controller
{
    // Menampilkan daftar To do Transfer
    public function index()
    {
        $roleUser = optional(Auth::user())->role;
        
        $query = AdmTodoTf::with('user')->orderBy('tgl', 'desc')->orderBy('status', 'asc');

        // Jika bukan admin, filter berdasarkan user_id
        if ($roleUser == 'admin') {
            $query->whereNotIn('status', [0, 2]);
        } else {
            $query->where('user_id', Auth::id());
        }

        $todos = $query->get();

        // Ambil nilai is_active dari AdmSetting dengan kode 'TDTF'
        $isActive = AdmSetting::where('kode', 'TDTF')->value('is_active');

        return view('pages.todo-transfer.index', compact('todos', 'isActive', 'roleUser'));
    }

    public function create()
    {
        $isActive = AdmSetting::where('kode', 'TDTF')->value('is_active');

        if($isActive){
            return view('pages.todo-transfer.create');
        }else{
            return redirect()->route('todo-transfer.index')->with('error', 'Pengajuan Transfer sedang ditutup.');
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'tgl' => 'required|date',
            'kode_lok' => 'required|string|max:255',
            'nama_toko' => 'required|string|max:255',
            'keterangan' => 'nullable|string',
            'bank' => 'required|string|max:255',
            'no_rek' => 'required|string|max:50',
            'nama_rek' => 'required|string|max:255',
            'nominal' => 'required|numeric|min:0',
        ]);

        AdmTodoTf::create([
            'tgl' => $request->tgl,
            'kode_lok' => $request->kode_lok,
            'nama_toko' => $request->nama_toko,
            'user_id' => Auth::id(),
            'keterangan' => $request->keterangan,
            'bank' => $request->bank,
            'no_rek' => $request->no_rek,
            'nama_rek' => $request->nama_rek,
            'nominal' => $request->nominal,
            'status' => 0, // Default status
        ]);

        return redirect()->route('todo-transfer.index')->with('success', 'To do Transfer berhasil dibuat.');
    }

    // Menampilkan detail To do Transfer
    public function show($id)
    {
        $todoTransfer = AdmTodoTf::with('bukti')->findOrFail($id);

        $roleUser = optional(Auth::user())->role;
        
        return view('pages.todo-transfer.detail', compact('todoTransfer', 'roleUser'));
    }

    public function edit($id)
    {
        $todoTransfer = AdmTodoTf::findOrFail($id);
        return view('pages.todo-transfer.edit', compact('todoTransfer'));
    }
    
    public function update(Request $request, $id)
    {
        $request->validate([
            'tgl' => 'required|date',
            'kode_lok' => 'required|string|max:255',
            'nama_toko' => 'required|string|max:255',
            'keterangan' => 'nullable|string',
            'bank' => 'required|string|max:255',
            'no_rek' => 'required|string|max:50',
            'nama_rek' => 'required|string|max:255',
            'nominal' => 'required|numeric|min:0',
        ]);
    
        $todoTransfer = AdmTodoTf::findOrFail($id);
        $todoTransfer->update($request->all());
    
        return redirect()->route('todo-transfer.show', $id)->with('success', 'Data berhasil diperbarui.');
    }

    public function updateStatus($id, $status)
    {
        $todoTransfer = AdmTodoTf::with('user')->findOrFail($id);

        if (in_array($todoTransfer->status, [3, 5])) {
            return back()->with('error', 'Data tidak dapat diubah.');
        }

        if ($todoTransfer->status == 0 && $status != 1) {
            return back()->with('error', 'Status hanya bisa diubah ke "Kirim" terlebih dahulu.');
        }

        if ($todoTransfer->status == 1 && !in_array($status, [2, 3, 4, 5])) {
            return back()->with('error', 'Status yang dipilih tidak valid.');
        }

        // Update status
        $todoTransfer->update(['status' => $status]);

        // Kirim email hanya jika status berubah ke 2 (Revisi), 3 (Ditolak), atau 5 (Sudah Ditransfer)
        if (in_array($status, [2, 3, 5]) && $todoTransfer->user && $todoTransfer->user->email) {
            try {
                Mail::to($todoTransfer->user->email)->send(new TodoTransferStatusUpdated($todoTransfer));
                Log::info("Email berhasil dikirim ke: " . $todoTransfer->user->email);
            } catch (\Exception $e) {
                Log::error("Gagal mengirim email: " . $e->getMessage());
            }
        } else if($status == 1){
            try {
                Mail::to("adpusatindogadai@gmail.com")->send(new TodoTransferStatusUpdated($todoTransfer));
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
            'adm_todo_tf_id' => 'required|exists:adm_todo_tf,id',
            'keterangan' => 'required|string|max:255',
            'foto' => 'required|image'
        ]);
    
        $path = $request->file('foto')->store('bukti_todo_tf', 'public');
    
        AdmTodoTfBukti::create([
            'adm_todo_tf_id' => $request->adm_todo_tf_id,
            'keterangan' => $request->keterangan,
            'foto' => $path
        ]);
    
        return back()->with('success', 'Bukti transfer berhasil ditambahkan.');
    }    

    // Menghapus bukti transfer
    public function deleteBukti($id)
    {
        $bukti = AdmTodoTfBukti::findOrFail($id);
        Storage::disk('public')->delete($bukti->foto);
        $bukti->delete();
    
        return back()->with('success', 'Bukti transfer berhasil dihapus.');
    }  
    
    public function export(Request $request)
    {
        $filters = [
            'status' => $request->query('status'),
            'start_date' => $request->query('start_date'),
            'end_date' => $request->query('end_date')
        ];
    
        return Excel::download(new TodoTransferExport($filters), 'todo_transfer.xlsx');
    }    
    
}

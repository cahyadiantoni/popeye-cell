<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\Negoan;
use App\Models\NegoanChat;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NegoanController extends Controller
{
    public function index()
    {
        $negoans = Negoan::with('user')
        ->orderBy('status', 'asc')
        ->orderBy('updated_at', 'desc')
        ->get();
    
        return view('pages.negoan.index', compact('negoans'));
    }    

    public function create()
    {
        // Get unique 'tipe' values from the Barang model
        $tipeList = Barang::select('tipe')->distinct()->pluck('tipe');

        return view('pages.negoan.create', compact('tipeList'));
    }

    public function getHargaAwal(Request $request)
    {
        $tipe = $request->input('tipe');
        $negoan = Negoan::where('tipe', $tipe)
                        ->where('status', 1)
                        ->orderBy('updated_at', 'desc')
                        ->first();

        return response()->json([
            'harga_awal' => $negoan ? $negoan->harga_acc : null,
        ]);
    }

    public function destroy($id)
    {
        // Find the Negoan record by ID
        $negoan = Negoan::find($id);

        // Check if the record exists
        if ($negoan) {
            // Delete the record
            $negoan->delete();

            // Redirect back to the index with a success message
            return redirect()->route('negoan.index')->with('success', 'Negoan berhasil dihapus.');
        } else {
            // Redirect back to the index with an error message if not found
            return redirect()->route('negoan.index')->with('error', 'Negoan tidak ditemukan.');
        }
    }

    public function store(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'tipe' => 'required|string|max:255',
            'harga_awal' => 'nullable|numeric',
            'harga_nego' => 'required|numeric',
            'note_nego' => 'nullable|string',
            'is_manual' => 'required', // Assuming you have a field to indicate manual input
            'harga_awal_manual' => 'nullable|numeric', // If you have a manual input field
        ]);

        // Create a new Negoan instance
        $negoan = new Negoan();
        $negoan->tipe = $request->tipe;
        $negoan->is_manual = $request->is_manual;
        if ($request->is_manual) {
            $negoan->harga_awal = $request->harga_awal_manual; // Use manual input
        } else {
            $negoan->harga_awal = $request->harga_awal; // Use fetched value
        }
        $negoan->harga_nego = $request->harga_nego;
        $negoan->note_nego = $request->note_nego;
        $negoan->status = 0; // Assuming default status is 'Proses'
        $negoan->user_id = auth()->id(); // Assuming the user is authenticated

        // Save the Negoan instance
        if ($negoan->save()) {
            return redirect()->route('negoan.index')->with('success', 'Negoan berhasil dibuat.');
        } else {
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menyimpan Negoan.')->withInput();
        }
    }

    public function show($id)
    {
        // Find the Negoan record by ID
        $negoan = Negoan::findOrFail($id);

        $roleUser = optional(Auth::user())->role;

        $chats = NegoanChat::where('t_negoan_id', $id)->with('user')->get();

        // Pass the Negoan record to the view
        return view('pages.negoan.detail', compact('negoan', 'chats', 'roleUser'));
    }

    public function storeChat(Request $request)
    {
        $AuthId = Auth::id();
        // Validate the incoming request
        $request->validate([
            't_negoan_id' => 'required|exists:t_negoan,id',
            'isi' => 'required|string|max:255',
        ]);

        // Create a new chat message
        $saveChat = NegoanChat::create([
            't_negoan_id' => $request->t_negoan_id,
            'user_id' => $AuthId,
            'isi' => $request->isi,
        ]);
        
        if ($saveChat) {
            // Retrieve the Negoan owner
            $negoan = Negoan::findOrFail($request->t_negoan_id);

            $pengirimId = $AuthId;
            $penerimaIds = NegoanChat::where('t_negoan_id', $request->t_negoan_id)
            ->whereNotIn('user_id', [10, $negoan->user_id, $AuthId])
            ->distinct()
            ->pluck('user_id')
            ->toArray();

            if ($AuthId != $negoan->user_id) {
                $penerimaIds[] = $negoan->user_id; 
            } 
            
            if($AuthId != 10){
                $penerimaIds[] = 10; 
            }

            foreach($penerimaIds as $penerimaId){
                // Create the notification
                Notification::create([
                    'pengirim_id' => $pengirimId,
                    'penerima_id' => $penerimaId,
                    'title' => 'Chat Negoan',
                    'isi' => 'Chat masuk : ' . $request->isi,
                    'link' => url('/negoan/' . $request->t_negoan_id),
                ]);
            }
        }

        return redirect()->back()->with('success', 'Message sent successfully!');
    }

    public function update(Request $request, $id)
    {
        // Validate the incoming request with custom messages
        $request->validate([
            'harga_acc' => 'required|numeric',
            'note_acc' => 'nullable|string',
            'status' => 'required|in:1,2', // Assuming status can be 0, 1, or 2
        ], [
            'harga_acc.required' => 'Harga Acc is required.',
            'harga_acc.numeric' => 'Harga Acc must be a number.',
            'note_acc.string' => 'Note Acc must be a string.',
            'status.required' => 'Status is required.',
            'status.in' => 'Status must be either 1 or 2.',
        ]);

        // Find the Negoan record by ID
        $negoan = Negoan::findOrFail($id);

        // Update the Negoan record with the validated data
        $negoan->update([
            'harga_acc' => $request->harga_acc,
            'note_acc' => $request->note_acc,
            'status' => $request->status,
        ]);

        // Redirect back to the index page with a success message
        return redirect()->back()->with('success', 'Negoan updated successfully.');
    }
}

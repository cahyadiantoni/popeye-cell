<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\Negoan;
use App\Models\NegoanChat;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;

class NegoanController extends Controller
{
    public function index(Request $request)
    {
        // Subquery: ambil ID terbaru untuk kombinasi unik
        $subQuery = Negoan::select(DB::raw('MAX(id) as id'))
            ->groupBy('tipe', 'grade', 'status');

        // Gunakan hasil subquery untuk ambil record lengkap
        $query = Negoan::whereIn('id', $subQuery);

        // Filter berdasarkan input
        if ($request->filled('grade')) {
            $query->where('grade', $request->grade);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('updated_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('updated_at', '<=', $request->end_date);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $negoans = $query->orderBy('status', 'asc')->orderBy('updated_at', 'desc')->get();

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
        $grade = $request->input('grade');

        $negoan = Negoan::where('tipe', $tipe)
                        ->where('grade', $grade)
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
            'grade' => 'required|string|max:255',
            'harga_awal' => 'nullable|numeric',
            'harga_nego' => 'required|numeric',
            'note_nego' => 'nullable|string',
            'is_manual' => 'required', // Assuming you have a field to indicate manual input
            'harga_awal_manual' => 'nullable|numeric', // If you have a manual input field
        ]);

        $tipe = $request->tipe;
        $grade = $request->grade;

        // Cek apakah sudah ada tipe dan grade yang sama dengan status == 0
        $existsStatusZero = Negoan::where('tipe', $tipe)
            ->where('grade', $grade)
            ->where('status', 0)
            ->exists();

        if ($existsStatusZero) {
            return redirect()->back()
                ->with('error', 'Sudah ada Negoan dengan tipe dan grade yang sama yang sedang dalam status Proses.')
                ->withInput();
        }

        // Cek apakah sudah ada tipe dan grade yang sama dalam hari yang sama
        $existsToday = Negoan::where('tipe', $tipe)
            ->where('grade', $grade)
            ->whereDate('updated_at', now()->toDateString())
            ->exists();

        if ($existsToday) {
            return redirect()->back()
                ->with('error', 'Sudah ada Negoan dengan tipe dan grade yang sama yang dibuat hari ini.')
                ->withInput();
        }

        // Create a new Negoan instance
        $negoan = new Negoan();
        $negoan->tipe = $request->tipe;
        $negoan->grade = $request->grade;
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

    public function storeUpload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
            'grade' => 'required|string|max:255',
        ]);

        try {
            $file = $request->file('file');
            $grade = $request->grade;
            $rows = Excel::toArray([], $file)[0]; // ambil sheet pertama

            if (count($rows) <= 1) {
                return redirect()->back()->with('error', 'File Excel kosong atau tidak memiliki data.');
            }

            $header = array_map('strtolower', $rows[0]);
            unset($rows[0]);

            $requiredColumns = ['tipe', 'harga_awal', 'harga_nego', 'note_nego'];
            foreach ($requiredColumns as $column) {
                if (!in_array($column, $header)) {
                    return redirect()->back()->with('error', "Kolom '{$column}' tidak ditemukan di file.");
                }
            }

            $count = 0;
            $errorMessages = [];
            $processedTipes = [];
            $excelTipes = [];

            foreach ($rows as $index => $row) {
                $data = array_combine($header, $row);
                $rowNumber = $index + 2; // +2 karena baris pertama header

                $tipe = trim($data['tipe'] ?? '');
                $hargaNego = $data['harga_nego'] ?? null;

                // Validasi baris kosong
                if ($tipe === '' || $hargaNego === null) {
                    $errorMessages[] = "Baris {$rowNumber}: Kolom 'tipe' dan 'harga_nego' wajib diisi.";
                    continue;
                }

                // Cek duplikat dalam file Excel
                if (in_array($tipe, $excelTipes)) {
                    $errorMessages[] = "Baris {$rowNumber}: Tipe '{$tipe}' duplikat dalam file Excel.";
                    continue;
                }
                $excelTipes[] = $tipe;

                // Cek duplikat di database (status = 0)
                $existsStatusZero = Negoan::where('tipe', $tipe)
                    ->where('grade', $grade)
                    ->where('status', 0)
                    ->exists();
                if ($existsStatusZero) {
                    $errorMessages[] = "Baris {$rowNumber}: Tipe '{$tipe}' sudah ada dengan status 'Proses'.";
                    continue;
                }

                // Cek duplikat hari ini
                $existsToday = Negoan::where('tipe', $tipe)
                    ->where('grade', $grade)
                    ->whereDate('updated_at', now()->toDateString())
                    ->exists();
                if ($existsToday) {
                    $errorMessages[] = "Baris {$rowNumber}: Tipe '{$tipe}' sudah diupload hari ini.";
                    continue;
                }

                // Simpan data
                Negoan::create([
                    'tipe' => $tipe,
                    'grade' => $grade,
                    'harga_awal' => isset($data['harga_awal']) ? $data['harga_awal'] * 1000 : null,
                    'harga_nego' => $hargaNego * 1000,
                    'note_nego' => $data['note_nego'] ?? null,
                    'status' => 0,
                    'is_manual' => 1,
                    'user_id' => auth()->id(),
                ]);

                $count++;
            }

            // Respon akhir
            if ($count > 0 && count($errorMessages) === 0) {
                return redirect()->route('negoan.index')->with('success', "Berhasil upload {$count} data.");
            } elseif ($count > 0 && count($errorMessages) > 0) {
                return redirect()->route('negoan.index')->with([
                    'success' => "Sebagian berhasil upload {$count} data.",
                    'error' => implode('<br>', $errorMessages),
                ]);
            } else {
                return redirect()->back()->with('error', 'Gagal upload. Tidak ada data yang valid.<br>' . implode('<br>', $errorMessages));
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan saat memproses file: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        // Find the Negoan record by ID
        $negoan = Negoan::findOrFail($id);

        $historyNego = Negoan::where('tipe', $negoan->tipe)
        ->where('grade', $negoan->grade)
        ->where('id', '!=', $negoan->id) // opsional, agar tidak termasuk data yang sedang ditampilkan
        ->with('user')
        ->orderBy('updated_at', 'asc')
        ->get();

        $roleUser = optional(Auth::user())->role;

        $chats = NegoanChat::where('t_negoan_id', $id)->with('user')->get();

        // Pass the Negoan record to the view
        return view('pages.negoan.detail', compact('negoan', 'historyNego', 'chats', 'roleUser'));
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

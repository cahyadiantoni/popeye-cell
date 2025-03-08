<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Kirim;
use App\Http\Controllers\Controller;
use App\Models\Gudang;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Auth;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class DataBarangController extends Controller
{
    public function index(Request $request)
    {
        $roleUser = optional(Auth::user())->role;
    
        if ($request->ajax()) {
            $query = Barang::with('gudang');
            return DataTables::of($query)
                ->addColumn('action', function ($barang) use ($roleUser) {
                    $editButton = '
                        <!-- Tombol Edit -->
                        <button type="button" class="btn btn-warning btn-round edit-barang-btn" 
                            data-lok_spk="' . htmlspecialchars($barang->lok_spk) . '" 
                            data-jenis="' . htmlspecialchars($barang->jenis) . '" 
                            data-tipe="' . htmlspecialchars($barang->tipe) . '" 
                            data-grade="' . htmlspecialchars($barang->grade) . '"
                            data-kelengkapan="' . htmlspecialchars($barang->kelengkapan) . '">
                            Edit
                        </button>
                    ';
    
                    $deleteButton = '';
                    if ($roleUser === 'admin') {
                        $deleteButton = '
                            <!-- Tombol Delete -->
                            <form action="' . route('data-barang.destroy', urlencode($barang->lok_spk)) . '" method="POST" style="display:inline;">
                                ' . csrf_field() . '
                                ' . method_field('DELETE') . '
                                <button type="submit" class="btn btn-danger btn-round" 
                                    onclick="return confirm(\'Are you sure you want to delete this barang?\')">
                                    Delete
                                </button>
                            </form>
                        ';
                    }
    
                    return $editButton . ' ' . $deleteButton;
                })
                ->editColumn('gudang.nama_gudang', function ($barang) {
                    return $barang->gudang->nama_gudang ?? 'N/A';
                })
                ->rawColumns(['action'])
                ->make(true);
        }
    
        return view('pages.data-barang.index');
    }    
    

    public function edit($lok_spk)
    {
        // Mencari barang berdasarkan lok_spk
        $barang = Barang::findOrFail($lok_spk);
        return view('pages.data-barang.edit', compact('barang'));
    }

    public function update(Request $request, $lok_spk)
    {
        // Mencari barang berdasarkan primary key lok_spk
        $barang = Barang::findOrFail($lok_spk);

        // Validasi input
        $validator = Validator::make($request->all(), [
            'jenis' => 'required|string',
            'merek' => 'required|string',
            'tipe' => 'required|string',
            'imei' => 'required|string',
            'kelengkapan' => 'required|string',
            'kerusakan' => 'required|string',
            'grade' => 'required|string',
            'gudang_id' => 'required|string',
            'status_barang' => 'required|integer',
            'qt_bunga' => 'required|string',
            'harga_jual' => 'required|numeric',
            'harga_beli' => 'required|numeric',
            'keterangan1' => 'nullable|string',
            'keterangan2' => 'nullable|string',
            'keterangan3' => 'nullable|string',
            'nama_petugas' => 'required|string',
            'dt_beli' => 'required|date',
            'dt_lelang' => 'required|date',
            'dt_jatuh_tempo' => 'required|date',
        ]);

        // Jika validasi gagal
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Update data barang
        $barang->update([
            'jenis' => $request->input('jenis'),
            'merek' => $request->input('merek'),
            'tipe' => $request->input('tipe'),
            'imei' => $request->input('imei'),
            'kelengkapan' => $request->input('kelengkapan'),
            'kerusakan' => $request->input('kerusakan'),
            'grade' => $request->input('grade'),
            'gudang_id' => $request->input('gudang_id'),
            'status_barang' => $request->input('status_barang'),
            'qt_bunga' => $request->input('qt_bunga'),
            'harga_jual' => $request->input('harga_jual'),
            'harga_beli' => $request->input('harga_beli'),
            'keterangan1' => $request->input('keterangan1'),
            'keterangan2' => $request->input('keterangan2'),
            'keterangan3' => $request->input('keterangan3'),
            'nama_petugas' => $request->input('nama_petugas'),
            'dt_beli' => $request->input('dt_beli'),
            'dt_lelang' => $request->input('dt_lelang'),
            'dt_jatuh_tempo' => $request->input('dt_jatuh_tempo'),
        ]);

        return redirect()->route('data-barang.index')->with('success', 'Barang updated successfully!');
    }

    public function create()
    {
        // Mengambil semua data gudang dari database
        $gudangs = Gudang::all();
        return view('pages.data-barang.create', compact('gudangs'));
    }

    public function massedit()
    {
        return view('pages.data-barang.mass-edit');
    }

    public function store(Request $request)
    {
        $request->validate([
            'filedata' => 'required|file|mimes:xlsx,xls'
        ]);
    
        // Inisialisasi array untuk pesan error dan baris yang berhasil disimpan
        $errors = [];
        $successfulRows = [];
        
        // Membaca file Excel
        $file = $request->file('filedata');
        $data = Excel::toArray([], $file);
        $gudang_id = $request->input('gudang_id');

        $gudang = Gudang::find($gudang_id);
        $pj_gudang = $gudang->pj_gudang;
    
        foreach ($data[0] as $index => $row) {
            // Lewati baris pertama jika merupakan header
            if ($index === 0) continue;
    
            // Ambil dan format tanggal
            $dt_beli = Carbon::createFromFormat('Y-m-d', '1900-01-01')->addDays($row[15] - 2)->format('Y-m-d');
            $dt_lelang = Carbon::createFromFormat('Y-m-d', '1900-01-01')->addDays($row[16] - 2)->format('Y-m-d');
            $dt_jatuh_tempo = Carbon::createFromFormat('Y-m-d', '1900-01-01')->addDays($row[17] - 2)->format('Y-m-d');
    
            // Validasi tipe data tiap kolom
            if (
                is_string($row[0]) && // lok_spk
                is_string($row[1]) && // jenis
                is_string($row[3]) && // tipe
                is_string($row[14]) // nama_petugas
            ) {
                // Cek apakah lok_spk sudah ada di database
                if (Barang::where('lok_spk', $row[0])->exists()) {
                    // Tambahkan error jika lok_spk sudah ada di database
                    $errors[] = "Row " . ($index + 1) . " has a duplicate lok_spk in database: ";
                    continue; // Lewati penyimpanan untuk row ini
                }
    
                // Simpan data ke database jika valid
                Barang::create([
                    'lok_spk' => $row[0],
                    'jenis' => $row[1],
                    'merek' => $row[2],
                    'tipe' => $row[3],
                    'imei' => $row[4],
                    'kelengkapan' => $row[5],
                    'kerusakan' => $row[6],
                    'grade' => $row[7],
                    'qt_bunga' => $row[8],
                    'harga_jual' => $row[9],
                    'harga_beli' => $row[10],
                    'keterangan1' => $row[11],
                    'keterangan2' => $row[12],
                    'keterangan3' => $row[13],
                    'nama_petugas' => $row[14],
                    'dt_beli' => $dt_beli,
                    'dt_lelang' => $dt_lelang,
                    'dt_jatuh_tempo' => $dt_jatuh_tempo,
                    'dt_input' => Carbon::now(),
                    'user_id' => Auth::id(),
                    'gudang_id' => $gudang_id,
                    'status_barang' => 1,
                ]);
                
                // Tambahkan baris yang berhasil disimpan
                $successfulRows[] = $index + 1; // Simpan nomor baris (index + 1 untuk tampilan)
            } else {
                // Tambahkan error jika tidak valid
                $errors[] = "Row " . ($index + 1) . " has invalid data: ";
            }
        }
    
        // Kembalikan pesan berhasil atau error
        if (count($errors) > 0 || count($successfulRows) > 0) {
            $successMessage = count($successfulRows) > 0 ? 
                'Rows successfully saved: ' . implode(', ', $successfulRows) : 
                '';
    
            return redirect()->route('data-barang.index')->with([
                'errors' => $errors,
                'success' => $successMessage
            ]);
        }
    
        return redirect()->route('data-barang.index')->with('success', 'No rows were processed successfully.');
    }    
    
    private function validateDate($date)
    {
        // Cek berbagai format tanggal yang diinginkan
        $formats = ['Y-m-d', 'd/m/Y', 'm-d-Y', 'Y.m.d', 'd.m.Y', 'm.d.Y', 'y-m-d', 'd/m/y', 'm-d-y'];
        foreach ($formats as $format) {
            $d = \DateTime::createFromFormat($format, $date);
            // Jika format cocok, kembalikan true
            if ($d && $d->format($format) === $date) {
                return true;
            }
        }
        // Jika semua format tidak cocok, kembalikan false
        return false;
    }

    public function destroy($lokSpk)
    {
        // Cari barang berdasarkan lok_spk dan hapus
        $barang = Barang::findOrFail($lokSpk);
        $barang->delete();

        return redirect()->route('data-barang.index')->with('success', 'Barang deleted successfully!');
    }

    public function updateDataBarang(Request $request, $lok_spk)
    {
        $validator = Validator::make($request->all(), [
            'lok_spk' => 'required|string|max:255',
            'jenis' => 'required|string|max:255',
            'tipe' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', 'Gagal Validasi Error!');
        }

        // Cek apakah `lok_spk` baru sudah ada di database, selain data yang sedang diupdate
        $existingBarang = Barang::where('lok_spk', $request->input('lok_spk'))
            ->where('lok_spk', '!=', $lok_spk)
            ->exists();

        if ($existingBarang) {
            return redirect()->back()->with('error', 'Gagal Lok SPK sudah digunakan!');
        }
        
        // Update data barang
        $barang = Barang::findOrFail($lok_spk);
        $barang->lok_spk = $request->input('lok_spk');
        $barang->jenis = $request->input('jenis');
        $barang->tipe = $request->input('tipe');
        $barang->grade = $request->input('grade');
        $barang->kelengkapan = $request->input('kelengkapan');
        $barang->save();
        
        return redirect()->back()->with('success', 'Data barang berhasil diperbarui.');
    }

    public function massUpdateDataBarang(Request $request)
    {
        $request->validate([
            'filedata' => 'required|file|mimes:xlsx,xls'
        ]);

        $errors = [];
        $updatedRows = [];

        $file = $request->file('filedata');
        $data = Excel::toArray([], $file);

        foreach ($data[0] as $index => $row) {
            if ($index === 0) continue; // Lewati header

            // Validasi bahwa lok_spk tidak boleh kosong
            if (empty($row[0])) {
                $errors[] = "Baris " . ($index + 1) . " tidak memiliki lok_spk.";
                continue;
            }

            $barang = Barang::where('lok_spk', $row[0])->first();

            if (!$barang) {
                $errors[] = "Baris " . ($index + 1) . " memiliki lok_spk yang tidak ditemukan.";
                continue;
            }

            // Array untuk menyimpan data yang akan diperbarui
            $updateData = [];

            if (!empty($row[1])) $updateData['jenis'] = $row[1];
            if (!empty($row[2])) $updateData['tipe'] = $row[2];
            if (!empty($row[3])) $updateData['kelengkapan'] = $row[3];
            if (!empty($row[4])) $updateData['grade'] = $row[4];
            
            if (!empty($updateData)) {
                $barang->update($updateData);
                $updatedRows[] = $index + 1;
            }
        }

        if (count($errors) > 0 || count($updatedRows) > 0) {
            $successMessage = count($updatedRows) > 0 ? 
                'Baris yang berhasil diperbarui: ' . implode(', ', $updatedRows) : 
                '';

            return redirect()->route('data-barang.index')->with([
                'errors' => $errors,
                'success' => $successMessage
            ]);
        }

        return redirect()->route('data-barang.index')->with('success', 'Tidak ada baris yang diperbarui.');
    }


}

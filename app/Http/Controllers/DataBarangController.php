<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\HistoryEditBarang;
use App\Http\Controllers\Controller;
use App\Models\Gudang;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Auth;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;

class DataBarangController extends Controller
{
    public function index(Request $request)
    {
        $roleUser = optional(Auth::user())->role;
    
        if ($request->ajax()) {
            $query = Barang::with('gudang');
            return DataTables::of($query)
                ->editColumn('created_at', function ($row) {
                    return Carbon::parse($row->created_at)->translatedFormat('d F Y');
                })
                ->addColumn('action', function ($barang) use ($roleUser) {
                    $deleteButton = '';
                    $editButton = '';
                    $terjual = '';
                
                    if ($barang->status_barang != 2) {
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

                        if ($roleUser === 'admin') {

                            $deleteButton = '
                                <!-- Tombol Delete -->
                                <form action="' . route('data-barang.destroy', $barang->lok_spk) . '" method="POST" style="display:inline;">
                                    ' . csrf_field() . '
                                    ' . method_field('DELETE') . '
                                    <button type="submit" class="btn btn-danger btn-round" 
                                        onclick="return confirm(\'Are you sure you want to delete this barang?\')">
                                        Delete
                                    </button>
                                </form>
                            ';
                        }
                    }else{
                        $terjual = '
                            <!-- Tombol Edit -->
                            <button type="button" class="btn btn-success btn-round">
                                Terjual
                            </button>
                        ';
                    }
    
    
                    return $editButton . ' ' . $deleteButton  . ' ' . $terjual;
                })
                ->editColumn('gudang.nama_gudang', function ($barang) {
                    return $barang->gudang->nama_gudang ?? 'N/A';
                })
                ->rawColumns(['action'])
                ->make(true);
        }
    
        return view('pages.data-barang.index', compact('roleUser'));
    }    

    public function edit($lok_spk)
    {
        // Mencari barang berdasarkan lok_spk
        $barang = Barang::findOrFail($lok_spk);
        return view('pages.data-barang.edit', compact('barang'));
    }

    // public function update(Request $request, $lok_spk)
    // {
    //     // Mencari barang berdasarkan primary key lok_spk
    //     $barang = Barang::findOrFail($lok_spk);

    //     // Validasi input
    //     $validator = Validator::make($request->all(), [
    //         'jenis' => 'required|string',
    //         'merek' => 'required|string',
    //         'tipe' => 'required|string',
    //         'imei' => 'required|string',
    //         'kelengkapan' => 'required|string',
    //         'kerusakan' => 'required|string',
    //         'grade' => 'required|string',
    //         'gudang_id' => 'required|string',
    //         'status_barang' => 'required|integer',
    //         'qt_bunga' => 'required|string',
    //         'harga_jual' => 'required|numeric',
    //         'harga_beli' => 'required|numeric',
    //         'keterangan1' => 'nullable|string',
    //         'keterangan2' => 'nullable|string',
    //         'keterangan3' => 'nullable|string',
    //         'nama_petugas' => 'required|string',
    //         'dt_beli' => 'required|date',
    //         'dt_lelang' => 'required|date',
    //         'dt_jatuh_tempo' => 'required|date',
    //     ]);

    //     // Jika validasi gagal
    //     if ($validator->fails()) {
    //         return redirect()->back()->withErrors($validator)->withInput();
    //     }

    //     // Update data barang
    //     $barang->update([
    //         'jenis' => $request->input('jenis'),
    //         'merek' => $request->input('merek'),
    //         'tipe' => $request->input('tipe'),
    //         'imei' => $request->input('imei'),
    //         'kelengkapan' => $request->input('kelengkapan'),
    //         'kerusakan' => $request->input('kerusakan'),
    //         'grade' => $request->input('grade'),
    //         'gudang_id' => $request->input('gudang_id'),
    //         'status_barang' => $request->input('status_barang'),
    //         'qt_bunga' => $request->input('qt_bunga'),
    //         'harga_jual' => $request->input('harga_jual'),
    //         'harga_beli' => $request->input('harga_beli'),
    //         'keterangan1' => $request->input('keterangan1'),
    //         'keterangan2' => $request->input('keterangan2'),
    //         'keterangan3' => $request->input('keterangan3'),
    //         'nama_petugas' => $request->input('nama_petugas'),
    //         'dt_beli' => $request->input('dt_beli'),
    //         'dt_lelang' => $request->input('dt_lelang'),
    //         'dt_jatuh_tempo' => $request->input('dt_jatuh_tempo'),
    //     ]);

    //     return redirect()->route('data-barang.index')->with('success', 'Barang updated successfully!');
    // }

    public function create()
    {
        // Mengambil semua data gudang dari database
        $gudangs = Gudang::all();
        $gudangId = optional(Auth::user())->gudang_id;

        return view('pages.data-barang.create', compact('gudangs', 'gudangId'));
    }

    public function massedit()
    {
        return view('pages.data-barang.mass-edit');
    }

    public function massUpdateDataBarang(Request $request)
    {
        $request->validate(['filedata' => 'required|file|mimes:xlsx,xls']);
        $errors = [];
        $updatedRows = [];
        $file = $request->file('filedata');
        $data = Excel::toArray(new \stdClass(), $file);

        foreach ($data[0] as $index => $row) {
            if ($index === 0) continue;
            if (empty($row[0])) {
                $errors[] = "Baris " . ($index + 1) . " tidak memiliki lok_spk.";
                continue;
            }
            $barang = Barang::where('lok_spk', $row[0])->first();
            if (!$barang) {
                $errors[] = "Baris " . ($index + 1) . " memiliki lok_spk yang tidak ditemukan.";
                continue;
            }
            $updateData = [];
            if (isset($row[1]) && !is_null($row[1])) $updateData['jenis'] = $row[1];
            if (isset($row[2]) && !is_null($row[2])) $updateData['tipe'] = $row[2];
            if (isset($row[3]) && !is_null($row[3])) $updateData['kelengkapan'] = $row[3];
            if (isset($row[4]) && !is_null($row[4])) $updateData['grade'] = $row[4];
            
            if (!empty($updateData)) {
                $barang->fill($updateData);
                if ($barang->isDirty()) {
                    $perubahan = $barang->getDirty();
                    $pesanPerubahan = [];
                    $counter = 1;

                    // 1. Buat pesan history SEBELUM di-save
                    foreach ($perubahan as $kolom => $nilaiBaru) {
                        $nilaiLama = $barang->getOriginal($kolom);
                        $pesanPerubahan[] = $counter . ". " . $kolom . " (" . ($nilaiLama ?? 'kosong') . ") menjadi (" . ($nilaiBaru ?? 'kosong') . ")";
                        $counter++;
                    }

                    // 2. Simpan perubahan ke database
                    $barang->save();
                    $updatedRows[] = $index + 1;

                    // 3. Simpan catatan riwayat
                    if (!empty($pesanPerubahan)) {
                        HistoryEditBarang::create([
                            'lok_spk'   => $barang->lok_spk,
                            'update'    => implode("\n", $pesanPerubahan),
                            'user_id'   => Auth::id(),
                        ]);
                    }
                }
            }
        }

        if (count($errors) > 0 || count($updatedRows) > 0) {
            $successMessage = count($updatedRows) > 0 ? 
                'Baris yang berhasil diperbarui: ' . implode(', ', $updatedRows) : '';
            return redirect()->route('data-barang.index')->with(['errors' => $errors, 'success' => $successMessage]);
        }
        return redirect()->route('data-barang.index')->with('success', 'Tidak ada baris yang diperbarui.');
    }

    public function masseditUser()
    {
        return view('pages.data-barang.mass-edit-user');
    }

    public function massUpdateDataBarangUser(Request $request)
    {
        $request->validate(['filedata' => 'required|file|mimes:xlsx,xls']);
        $errors = [];
        $updatedRows = [];
        $file = $request->file('filedata');
        $data = Excel::toArray(new \stdClass(), $file);

        foreach ($data[0] as $index => $row) {
            if ($index === 0) continue;
            if (empty($row[0])) {
                $errors[] = "Baris " . ($index + 1) . " tidak memiliki lok_spk.";
                continue;
            }
            $barang = Barang::where('lok_spk', $row[0])->first();
            if (!$barang) {
                $errors[] = "Baris " . ($index + 1) . " memiliki lok_spk yang tidak ditemukan.";
                continue;
            }
            $updateData = [];
            if (isset($row[1]) && !is_null($row[1])) $updateData['jenis'] = $row[1];
            if (isset($row[2]) && !is_null($row[2])) $updateData['kelengkapan'] = $row[2];
            if (isset($row[3]) && !is_null($row[3])) $updateData['grade'] = $row[3];
            
            if (!empty($updateData)) {
                $barang->fill($updateData);
                if ($barang->isDirty()) {
                    $perubahan = $barang->getDirty();
                    $pesanPerubahan = [];
                    $counter = 1;

                    // 1. Buat pesan history SEBELUM di-save
                    foreach ($perubahan as $kolom => $nilaiBaru) {
                        $nilaiLama = $barang->getOriginal($kolom);
                        $pesanPerubahan[] = $counter . ". " . $kolom . " (" . ($nilaiLama ?? 'kosong') . ") menjadi (" . ($nilaiBaru ?? 'kosong') . ")";
                        $counter++;
                    }

                    // 2. Simpan perubahan ke database
                    $barang->save();
                    $updatedRows[] = $index + 1;

                    // 3. Simpan catatan riwayat
                    if (!empty($pesanPerubahan)) {
                        HistoryEditBarang::create([
                            'lok_spk'   => $barang->lok_spk,
                            'update'    => implode("\n", $pesanPerubahan),
                            'user_id'   => Auth::id(),
                        ]);
                    }
                }
            }
        }

        if (count($errors) > 0 || count($updatedRows) > 0) {
            $successMessage = count($updatedRows) > 0 ? 
                'Baris yang berhasil diperbarui: ' . implode(', ', $updatedRows) : '';
            return redirect()->route('data-barang.index')->with(['errors' => $errors, 'success' => $successMessage]);
        }
        return redirect()->route('data-barang.index')->with('success', 'Tidak ada baris yang diperbarui.');
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
    
            // Validasi tipe data tiap kolom
            if (
                is_string($row[0]) && 
                is_string($row[1]) && 
                is_string($row[2]) 
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
                    'tipe' => $row[2],
                    'imei' => $row[3],
                    'kelengkapan' => $row[4],
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

    public function destroy(Barang $data_barang)
    {
        try {
            DB::transaction(function () use ($data_barang) {
                
                // 1. Catat riwayat SEBELUM menghapus
                HistoryEditBarang::create([
                    'lok_spk'   => $data_barang->lok_spk, // Simpan LOK SPK-nya
                    'update'    => "Barang dengan tipe '{$data_barang->tipe}' dihapus dari sistem.",
                    'user_id'   => auth()->id(),
                ]);

                // 2. Hapus barang setelah riwayat dicatat
                $data_barang->delete();

            });

            return redirect()->route('data-barang.index')->with('success', 'Barang berhasil dihapus.');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus barang: ' . $e->getMessage());
        }
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

        $existingBarang = Barang::where('lok_spk', $request->input('lok_spk'))
            ->where('lok_spk', '!=', $lok_spk)
            ->exists();

        if ($existingBarang) {
            return redirect()->back()->with('error', 'Gagal Lok SPK sudah digunakan!');
        }
        
        $barang = Barang::findOrFail($lok_spk);

        // Isi model dengan data baru dari request
        $barang->lok_spk = $request->input('lok_spk');
        $barang->jenis = $request->input('jenis');
        $barang->tipe = $request->input('tipe');
        $barang->grade = $request->input('grade');
        $barang->kelengkapan = $request->input('kelengkapan');

        // Cek apakah ada perubahan pada data
        if ($barang->isDirty()) {
            // === LOGIKA BARU DIMULAI DI SINI ===
            
            $perubahan = $barang->getDirty(); // Dapatkan array perubahan ['kolom' => 'nilai_baru']
            $pesanPerubahan = [];
            $counter = 1;

            // 1. Buat pesan history SEBELUM di-save
            foreach ($perubahan as $kolom => $nilaiBaru) {
                $nilaiLama = $barang->getOriginal($kolom); // Ambil nilai lama SEBELUM save()
                $pesanPerubahan[] = $counter . ". " . $kolom . " (" . ($nilaiLama ?? 'kosong') . ") menjadi (" . ($nilaiBaru ?? 'kosong') . ")";
                $counter++;
            }

            // 2. Simpan perubahan data barang ke database
            $barang->save();

            // 3. Simpan catatan riwayat dengan pesan yang sudah dibuat tadi
            if (!empty($pesanPerubahan)) {
                HistoryEditBarang::create([
                    'lok_spk'   => $barang->lok_spk,
                    'update'    => implode("\n", $pesanPerubahan),
                    'user_id'   => Auth::id(),
                ]);
            }
            // === LOGIKA BARU SELESAI ===
        }
        
        return redirect()->back()->with('success', 'Data barang berhasil diperbarui.');
    }

    public function pendingan(Request $request)
    {
        $roleUser = optional(Auth::user())->role;
    
        if ($request->ajax()) {
            $query = Barang::with('gudang')
                ->where('status_barang', 4)
                ->orderBy('updated_at', 'desc')
                ->get();
            return DataTables::of($query)
                ->editColumn('created_at', function ($row) {
                    return Carbon::parse($row->created_at)->translatedFormat('d F Y');
                })
                ->addColumn('action', function ($barang) use ($roleUser) {
                    $deleteButton = '';
                
                    $deleteButton = '
                        <!-- Tombol Delete -->
                        <form action="' . route('data-barang-pendingan.delete', urlencode($barang->lok_spk)) . '" method="POST" style="display:inline;">
                            ' . csrf_field() . '
                            ' . method_field('DELETE') . '
                            <button type="submit" class="btn btn-danger btn-round" 
                                onclick="return confirm(\'Are you sure you want to delete this barang?\')">
                                Delete
                            </button>
                        </form>
                    ';
    
    
                    return $deleteButton;
                })
                ->editColumn('gudang.nama_gudang', function ($barang) {
                    return $barang->gudang->nama_gudang ?? 'N/A';
                })
                ->rawColumns(['action'])
                ->make(true);
        }
    
        return view('pages.data-barang.pendingan');
    }  
    
    public function storePendingan(Request $request)
    {
        $request->validate([
            'filedata' => 'required|file|mimes:xlsx,xls'
        ]);

        $file = $request->file('filedata');
        $data = Excel::toArray([], $file);

        $errors = [];
        $updatedRows = [];

        foreach ($data[0] as $index => $row) {
            // Lewati header
            if ($index === 0) continue;

            $lokSpk = $row[0] ?? null;

            if (is_null($lokSpk) || !is_string($lokSpk)) {
                $errors[] = "Row " . ($index + 1) . " has invalid or empty lok_spk.";
                continue;
            }

            $barang = Barang::where('lok_spk', $lokSpk)->first();

            if (!$barang) {
                $errors[] = "Row " . ($index + 1) . " lok_spk not found in database: $lokSpk";
                continue;
            }

            if (in_array($barang->status_barang, [0, 1])) {
                $barang->update(['status_barang' => 4]);
                $updatedRows[] = $index + 1;
            } else {
                $errors[] = "Row " . ($index + 1) . " has status_barang not eligible for update (status: {$barang->status_barang})";
            }
        }

        // Redirect dengan notifikasi seperti metode store biasa
        if (count($errors) > 0 || count($updatedRows) > 0) {
            $successMessage = count($updatedRows) > 0 ? 
                'Rows successfully updated: ' . implode(', ', $updatedRows) : 
                '';

            return redirect()->route('data-barang-pendingan.index')->with([
                'errors' => $errors,
                'success' => $successMessage
            ]);
        }

        return redirect()->route('data-barang-pendingan.index')->with('success', 'No rows were processed successfully.');
    }

    public function deletePendingan($lokSpk)
    {
        // Cari barang berdasarkan lok_spk
        $barang = Barang::where('lok_spk', $lokSpk)->first();

        if (!$barang) {
            return redirect()->route('data-barang.index')->with('error', 'Barang not found!');
        }

        // Ubah status_barang menjadi 1
        $barang->update(['status_barang' => 1]);

        return redirect()->route('data-barang-pendingan.index')->with('success', 'Status barang berhasil diubah menjadi 1!');
    }

}

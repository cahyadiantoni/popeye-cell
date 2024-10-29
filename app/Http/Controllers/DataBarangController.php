<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Auth;
use Illuminate\Support\Facades\Validator;

class DataBarangController extends Controller
{
    public function index()
    {
        // Mengambil semua pengguna dari database
        $barangs = Barang::all(); // Ganti dengan metode sesuai kebutuhan
        return view('pages.data-barang.index', compact('barangs'));
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
        return view('pages.data-barang.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'filedata' => 'required|file|mimes:xlsx,xls'
        ]);

        // Inisialisasi array untuk pesan error
        $errors = [];
        
        // Membaca file Excel
        $file = $request->file('filedata');
        $data = Excel::toArray([], $file);
        $gudang = $request->input('gudang_id');

        foreach ($data[0] as $index => $row) {
            // Lewati baris pertama jika merupakan header
            if ($index === 0) continue;

            $dt_beli = Carbon::createFromFormat('Y-m-d', '1900-01-01')->addDays($row[15] - 2)->format('Y-m-d');
            $dt_lelang = Carbon::createFromFormat('Y-m-d', '1900-01-01')->addDays($row[16] - 2)->format('Y-m-d');
            $dt_jatuh_tempo = Carbon::createFromFormat('Y-m-d', '1900-01-01')->addDays($row[17] - 2)->format('Y-m-d');

            // Validasi tipe data tiap kolom
            if (
                is_string($row[0]) && // lok_spk
                is_string($row[1]) && // jenis
                is_string($row[2]) && // merek
                is_string($row[3]) && // tipe
                is_string($row[5]) && // kelengkapan
                is_string($row[6]) && // kerusakan
                is_string($row[7]) && // grade
                is_numeric($row[9]) && // harga_jual
                is_numeric($row[10]) && // harga_beli
                is_string($row[11]) && // keterangan1
                is_string($row[12]) && // keterangan2
                is_string($row[13]) && // keterangan3
                is_string($row[14]) // nama_petugas
            ) {
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
                    'gudang_id' => $gudang,
                ]);
            } else {
                // Tambahkan error jika tidak valid
                $errors[] = "Row " . ($index + 1) . " has invalid data: " . json_encode($row);
            }
        }

        // Kembalikan pesan berhasil atau error
        if (count($errors) > 0) {
            return redirect()->route('data-barang.index')->with('errors', $errors);
        }
        return redirect()->route('data-barang.index')->with('success', 'File successfully uploaded and processed!');
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

}

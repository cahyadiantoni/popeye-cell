<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Auth;
use Illuminate\Support\Facades\DB;


class DataBarangController extends Controller
{
    public function index()
    {
        // Mengambil semua pengguna dari database
        $barangs = Barang::all(); // Ganti dengan metode sesuai kebutuhan
        return view('pages.data-barang.index', compact('barangs'));
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
        // Menghapus barang berdasarkan `lok_spk`
        $deletedRows = DB::table('t_barang')->where('lok_spk', $lokSpk)->delete();

        if ($deletedRows > 0) {
            // Jika barang berhasil dihapus
            return back()->with('success', 'Data barang dengan lok_spk ' . $lokSpk . ' berhasil dihapus!');
        } else {
            // Jika tidak ada barang yang dihapus
            return back()->with('error', 'Data barang dengan lok_spk ' . $lokSpk . ' tidak ditemukan.');
        }
    }

}

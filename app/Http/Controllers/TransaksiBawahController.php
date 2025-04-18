<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\FakturBukti;
use App\Models\Gudang;
use App\Models\Negoan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Kirim;
use App\Models\FakturBawah;
use App\Models\TransaksiJualBawah;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class TransaksiBawahController extends Controller
{
    public function index()
    {
        $allgudangs = Gudang::all();
        return view('pages.transaksi-jual-bawah.index', compact('allgudangs'));
    }

    public function getData(Request $request)
    {
        if ($request->ajax()) {
            $barangs = Barang::join('t_jual_bawah', 't_barang.lok_spk', '=', 't_jual_bawah.lok_spk') // Join t_barang dengan t_jual_bawah
                ->join('t_faktur_bawah', 't_jual_bawah.nomor_faktur', '=', 't_faktur_bawah.nomor_faktur') // Join t_jual_bawah dengan t_faktur_bawah
                ->select(
                    't_barang.lok_spk',
                    't_barang.tipe',
                    't_faktur_bawah.nomor_faktur as nomor_faktur', // Mengambil nomor_faktur dari t_faktur_bawah
                    't_jual_bawah.harga as harga_jual', // Mengambil harga dari t_jual_bawah
                    't_barang.status_barang',
                    't_faktur_bawah.pembeli as pembeli_faktur', // Alias pembeli dari t_faktur_bawah
                    't_faktur_bawah.tgl_jual',
                    't_faktur_bawah.petugas as petugas_faktur'  // Alias petugas dari t_faktur_bawah
                )
                ->where('t_barang.status_barang', 2)
                ->orderBy('t_faktur_bawah.tgl_jual', 'desc');

            return DataTables::of($barangs)
                ->addColumn('harga_jual', function ($barang) {
                    return 'Rp. ' . number_format($barang->harga_jual, 0, ',', '.'); // Format harga ke Rupiah
                })
                ->filterColumn('pembeli', function ($query, $keyword) {
                    $query->where('t_faktur_bawah.pembeli', 'like', "%{$keyword}%");
                })
                ->filterColumn('petugas', function ($query, $keyword) {
                    $query->where('t_faktur_bawah.petugas', 'like', "%{$keyword}%");
                })
                ->filterColumn('nomor_faktur', function ($query, $keyword) {
                    $query->where('t_faktur_bawah.nomor_faktur', 'like', "%{$keyword}%");
                })
                ->make(true);
        }
    }   

    public function create()
    {   
        $gudangId = optional(Auth::user())->gudang_id;

        return view('pages.transaksi-jual-bawah.create',compact('gudangId'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'filedata' => 'required|file|mimes:xlsx,xls',
            'tgl_jual' => 'required|date',
            'nomor_faktur' => 'required|string',
            'pembeli' => 'required|string',
            'petugas' => 'required|string',
            'grade' => 'required|string',
        ]);

        // Cek apakah nomor_faktur sudah ada di tabel FakturBawah
        $existingFaktur = FakturBawah::where('nomor_faktur', $request->input('nomor_faktur'))->exists();
        if ($existingFaktur) {
            return redirect()->back()->with('error', 'Gagal disimpan: Nomor FakturBawah sudah ada. Harap diganti!');
        }

        // Inisialisasi variabel
        $errors = [];
        $totalHargaJual = 0;
        $validLokSpk = [];
        $processedLokSpk = []; // Array untuk memeriksa duplikat lok_spk

        // Membaca file Excel
        $file = $request->file('filedata');
        $data = Excel::toArray([], $file);

        foreach ($data[0] as $index => $row) {
            // Lewati baris pertama jika merupakan header
            if ($index === 0) continue;

            // Validasi kolom di Excel
            if (isset($row[0]) && isset($row[1])) {
                $lokSpk = $row[0]; // Lok SPK
                $hargaJual = $row[1] * 1000; // Harga Jual

                // Cek apakah lok_spk sudah ada di file Excel (duplikat dalam satu kali store)
                if (in_array($lokSpk, $processedLokSpk)) {
                    $errors[] = "Row " . ($index + 1) . ": Lok SPK '$lokSpk' duplikat di dalam file Excel.";
                    continue;
                }

                // Tambahkan lok_spk ke daftar yang sudah diproses
                $processedLokSpk[] = $lokSpk;

                // Cari barang berdasarkan lok_spk
                $barang = Barang::where('lok_spk', $lokSpk)->first();

                if ($barang) {
                    if (in_array($barang->status_barang, [0, 1])) {
                        $tipe = $barang->tipe;
                        $grade = $request->input('grade');
                        $tglJual = $request->input('tgl_jual');
                
                        // Ambil harga sebelumnya pada tanggal yang sama, untuk tipe dan grade ini
                        $hargaSebelumnya = TransaksiJualBawah::join('t_barang', 't_jual_bawah.lok_spk', '=', 't_barang.lok_spk')
                            ->join('t_faktur_bawah', 't_faktur_bawah.nomor_faktur', '=', 't_jual_bawah.nomor_faktur')
                            ->whereDate('t_faktur_bawah.tgl_jual', $tglJual)
                            ->where('t_barang.tipe', $tipe)
                            ->where('t_faktur_bawah.grade', $grade)
                            ->pluck('t_jual_bawah.harga')
                            ->unique();
                
                        // Jika sudah ada harga yang berbeda → tolak
                        if ($hargaSebelumnya->count() > 0 && !$hargaSebelumnya->contains($hargaJual)) {
                            $hargaList = $hargaSebelumnya->implode(', ');
                            $errors[] = "Row " . ($index + 1) . ": Harga jual $hargaJual berbeda dengan transaksi sebelumnya untuk tipe '$tipe', grade '$grade' pada tanggal $tglJual (harga sebelumnya: $hargaList).";
                            continue;
                        }
                
                        // Tambahkan harga_jual ke total dan simpan
                        $totalHargaJual += $hargaJual;
                        $validLokSpk[] = [
                            'lok_spk' => $lokSpk,
                            'harga_jual' => $hargaJual,
                        ];
                    } else {
                        $errors[] = "Row " . ($index + 1) . ": Lok SPK '$lokSpk' memiliki status_barang yang tidak sesuai.";
                    }
                } else {
                    $errors[] = "Row " . ($index + 1) . ": Lok SPK '$lokSpk' tidak ditemukan.";
                }
            } else {
                $errors[] = "Row " . ($index + 1) . ": Data tidak valid (Lok SPK atau harga jual kosong).";
            }
        }

        // Simpan data FakturBawah jika ada data valid
        if (!empty($validLokSpk)) {
            $newFaktur = FakturBawah::create([
                'nomor_faktur' => $request->input('nomor_faktur'),
                'pembeli' => $request->input('pembeli'),
                'tgl_jual' => $request->input('tgl_jual'),
                'petugas' => $request->input('petugas'),
                'grade' => $request->input('grade'),
                'keterangan' => $request->input('keterangan'),
                'total' => $totalHargaJual,
            ]);

            // Update Barang untuk lok_spk yang valid
            foreach ($validLokSpk as $item) {
                $tipe = Barang::where('lok_spk', $item['lok_spk'])
               ->pluck('tipe')
               ->first();
                
                $grade = $request->input('grade');

                $negoan = Negoan::where('tipe', $tipe)
                        ->where('grade', $grade)
                        ->where('status', 1)
                        ->orderBy('updated_at', 'desc')
                        ->first();

                Barang::where('lok_spk', $item['lok_spk'])->update([
                    'status_barang' => 2,
                    'no_faktur' => $request->input('nomor_faktur'),
                    'harga_jual' => $item['harga_jual'], // Update harga_jual dari Excel
                ]);

                TransaksiJualBawah::create([
                    'lok_spk' => $item['lok_spk'],
                    'nomor_faktur' => $request->input('nomor_faktur'),
                    'harga' => $item['harga_jual'],
                    'harga_acc' => $negoan->harga_acc ?? 0,
                ]);
            }

            // Tampilkan pesan sukses dan error
            return redirect()->route('transaksi-faktur-bawah.show', ['nomor_faktur' => $request->input('nomor_faktur')])
                ->with('success', 'FakturBawah berhasil disimpan. ' . count($validLokSpk) . ' barang diproses.')
                ->with('errors', $errors);
        }

        // Jika tidak ada data valid, hanya tampilkan error
        return redirect()->back()->with('errors', $errors);
    }
    

    public function destroy($id)
    {
        try {
            $transaksi = TransaksiJualBawah::where('id', $id)->firstOrFail();

            // Mendapatkan lok_spk dari transaksi
            $lok_spk = $transaksi->lok_spk;

            // Melakukan update pada tabel Barang
            Barang::where('lok_spk', $lok_spk)->update([
                'status_barang' => 1,
                'no_faktur' => null,
                'harga_jual' => 0, 
            ]);

            // Hapus Transaksi
            $nomorFaktur = $transaksi->nomor_faktur;
            $transaksi->delete();

            // Hitung ulang total pada FakturBawah
            $totalBaru = TransaksiJualBawah::where('nomor_faktur', $nomorFaktur)->sum('harga');
            FakturBawah::where('nomor_faktur', $nomorFaktur)->update(['total' => $totalBaru]);

            return redirect()->back()->with('success', 'Barang berhasil dihapus');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }


    public function update(Request $request)
    {
        try {
            $validated = $request->validate([
                'id' => 'required|exists:t_jual_bawah,id',
                'lok_spk' => 'required|exists:t_jual_bawah,lok_spk',
                'harga' => 'required|numeric|min:0',
            ]);
    
            // Gunakan firstOrFail() untuk pencarian berdasarkan 'id'
            $transaksi = TransaksiJualBawah::where('id', $validated['id'])->firstOrFail();
            $transaksi->update(['harga' => $validated['harga']]);
    
            // Update harga_jual pada model Barang
            $barang = $transaksi->barang;
            $barang->update(['harga_jual' => $validated['harga']]);
    
            // Hitung ulang total pada FakturBawah
            $nomorFaktur = $transaksi->nomor_faktur;
            $totalBaru = TransaksiJualBawah::where('nomor_faktur', $nomorFaktur)->sum('harga');
            FakturBawah::where('nomor_faktur', $nomorFaktur)->update(['total' => $totalBaru]);
    
            return redirect()->back()->with('success', 'Harga berhasil diupdate');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
    
    public function getSuggestNoFak(Request $request)
    {
        $kodeFaktur = $request->kode_faktur;
        $tglJual = $request->tgl_jual ? Carbon::parse($request->tgl_jual) : Carbon::now(); 
        $currentMonthYear = $tglJual->format('my'); // Menggunakan tanggal yang dipilih user

        // Ambil faktur terakhir dengan format yang sesuai
        $lastFaktur = FakturBawah::where('nomor_faktur', 'like', "$kodeFaktur-$currentMonthYear-%")
            ->orderByRaw("CAST(SUBSTRING(nomor_faktur, 10, LENGTH(nomor_faktur) - 9) AS UNSIGNED) DESC")
            ->first();

        // Tentukan nomor urut
        if ($lastFaktur) {
            preg_match('/-(\d+)$/', $lastFaktur->nomor_faktur, $matches);
            $lastNumber = isset($matches[1]) ? (int) $matches[1] : 0;
            $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '001';
        }

        // Format nomor faktur baru
        $suggestedNoFak = "$kodeFaktur-$currentMonthYear-$newNumber";

        return response()->json(['suggested_no_fak' => $suggestedNoFak]);
    }

}

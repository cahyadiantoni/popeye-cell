<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\FakturBuktiOutlet;
use App\Models\Gudang;
use App\Models\Negoan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Kirim;
use App\Models\FakturOutlet;
use App\Models\TransaksiJualOutlet;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class TransaksiOutletController extends Controller
{
    public function index()
    {
        $allgudangs = Gudang::all();
        return view('pages.transaksi-jual-outlet.index', compact('allgudangs'));
    }

    public function getData(Request $request)
    {
        if ($request->ajax()) {
            $barangs = Barang::join('t_jual_outlet', 't_barang.lok_spk', '=', 't_jual_outlet.lok_spk') // Join t_barang dengan t_jual_outlet
                ->join('t_faktur_outlet', 't_jual_outlet.nomor_faktur', '=', 't_faktur_outlet.nomor_faktur') // Join t_jual_outlet dengan t_faktur_outlet
                ->select(
                    't_barang.lok_spk',
                    't_barang.tipe',
                    't_faktur_outlet.nomor_faktur as nomor_faktur', // Mengambil nomor_faktur dari t_faktur_outlet
                    't_jual_outlet.harga as harga_jual', // Mengambil harga dari t_jual_outlet
                    't_barang.status_barang',
                    't_faktur_outlet.pembeli as pembeli_faktur', // Alias pembeli dari t_faktur_outlet
                    't_faktur_outlet.tgl_jual',
                    't_faktur_outlet.petugas as petugas_faktur'  // Alias petugas dari t_faktur_outlet
                )
                ->where('t_barang.status_barang', 2)
                ->orderBy('t_faktur_outlet.tgl_jual', 'desc');

            return DataTables::of($barangs)
                ->addColumn('harga_jual', function ($barang) {
                    return 'Rp. ' . number_format($barang->harga_jual, 0, ',', '.'); // Format harga ke Rupiah
                })
                ->addColumn('nomor_faktur', function ($barang) {
                    $url = route('transaksi-faktur-outlet.show', $barang->nomor_faktur);
                    return '<a href="' . $url . '" class="btn btn-info btn-sm" target="_blank">' . $barang->nomor_faktur . '</a>';
                })  
                ->filterColumn('pembeli', function ($query, $keyword) {
                    $query->where('t_faktur_outlet.pembeli', 'like', "%{$keyword}%");
                })
                ->filterColumn('petugas', function ($query, $keyword) {
                    $query->where('t_faktur_outlet.petugas', 'like', "%{$keyword}%");
                })
                ->filterColumn('nomor_faktur', function ($query, $keyword) {
                    $query->where('t_faktur_outlet.nomor_faktur', 'like', "%{$keyword}%");
                })
                ->rawColumns(['nomor_faktur'])
                ->make(true);
        }
    }   

    public function create()
    {   
        $gudangId = optional(Auth::user())->gudang_id;

        return view('pages.transaksi-jual-outlet.create',compact('gudangId'));
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

        // Cek apakah nomor_faktur sudah ada di tabel FakturOutlet
        $existingFaktur = FakturOutlet::where('nomor_faktur', $request->input('nomor_faktur'))->exists();
        if ($existingFaktur) {
            return redirect()->back()->with('error', 'Gagal disimpan: Nomor FakturOutlet sudah ada. Harap diganti!');
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
                    // Cek apakah status_barang adalah 0 atau 1
                    if (in_array($barang->status_barang, [0, 1])) {
                        // Tambahkan harga_jual ke total
                        $totalHargaJual += $hargaJual;

                        // Simpan lok_spk untuk update nanti
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

        // Simpan data FakturOutlet jika ada data valid
        if (!empty($validLokSpk)) {
            $newFaktur = FakturOutlet::create([
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

                TransaksiJualOutlet::create([
                    'lok_spk' => $item['lok_spk'],
                    'nomor_faktur' => $request->input('nomor_faktur'),
                    'harga' => $item['harga_jual'],
                    'harga_acc' => $negoan->harga_acc ?? 0,
                ]);
            }

            // Check if the 'foto' file and 'nominal' input are provided
            if ($request->hasFile('foto') && $request->input('nominal') !== null) {
                $path = $request->file('foto')->store('faktur_bukti', 'public');
                
                // Create the new FakturBuktiOutlet record
                $fakturBukti = FakturBuktiOutlet::create([
                    't_faktur_id' => $newFaktur->id,
                    'nominal' => $request->input('nominal'),
                    'foto' => $path
                ]);
                
                // Calculate the total nominal of all FakturBuktiOutlet records associated with the given t_faktur_id
                $totalNominal = FakturBuktiOutlet::where('t_faktur_id', $newFaktur->id)->sum('nominal');
                
                // Retrieve the FakturOutlet record
                $faktur = FakturOutlet::find($newFaktur->id);
                
                // Check if the total nominal is equal to or greater than the total in the FakturOutlet model
                if ($totalNominal >= $faktur->total) {
                    $faktur->is_lunas = 1;
                    $faktur->update();
                } else {
                    $faktur->is_lunas = 0;
                    $faktur->update();
                }
            }

            // Tampilkan pesan sukses dan error
            return redirect()->route('transaksi-faktur-outlet.show', ['nomor_faktur' => $request->input('nomor_faktur')])
                ->with('success', 'FakturOutlet berhasil disimpan. ' . count($validLokSpk) . ' barang diproses.')
                ->with('errors', $errors);
        }

        // Jika tidak ada data valid, hanya tampilkan error
        return redirect()->back()->with('errors', $errors);
    }
    

    public function destroy($id)
    {
        try {
            $transaksi = TransaksiJualOutlet::where('id', $id)->firstOrFail();

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

            // Hitung ulang total pada FakturOutlet
            $totalBaru = TransaksiJualOutlet::where('nomor_faktur', $nomorFaktur)->sum('harga');
            FakturOutlet::where('nomor_faktur', $nomorFaktur)->update(['total' => $totalBaru]);

            return redirect()->back()->with('success', 'Barang berhasil dihapus');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }


    public function update(Request $request)
    {
        try {
            $validated = $request->validate([
                'id' => 'required|exists:t_jual_outlet,id',
                'lok_spk' => 'required|exists:t_jual_outlet,lok_spk',
                'harga' => 'required|numeric|min:0',
            ]);
    
            // Gunakan firstOrFail() untuk pencarian berdasarkan 'id'
            $transaksi = TransaksiJualOutlet::where('id', $validated['id'])->firstOrFail();
            $transaksi->update(['harga' => $validated['harga']]);
    
            // Update harga_jual pada model Barang
            $barang = $transaksi->barang;
            $barang->update(['harga_jual' => $validated['harga']]);
    
            // Hitung ulang total pada FakturOutlet
            $nomorFaktur = $transaksi->nomor_faktur;
            $totalBaru = TransaksiJualOutlet::where('nomor_faktur', $nomorFaktur)->sum('harga');
            FakturOutlet::where('nomor_faktur', $nomorFaktur)->update(['total' => $totalBaru]);
    
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
        $lastFaktur = FakturOutlet::where('nomor_faktur', 'like', "$kodeFaktur-$currentMonthYear-%")
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

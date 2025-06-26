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
use App\Models\Faktur;
use App\Models\TransaksiJual;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;

class TransaksiController extends Controller
{
    public function index()
    {
        $allgudangs = Gudang::all();
        return view('pages.transaksi-jual.index', compact('allgudangs'));
    }

    public function getData(Request $request)
    {
        if ($request->ajax()) {
            $barangs = Barang::join('t_jual', 't_barang.lok_spk', '=', 't_jual.lok_spk') // Join t_barang dengan t_jual
                ->join('t_faktur', 't_jual.nomor_faktur', '=', 't_faktur.nomor_faktur') // Join t_jual dengan t_faktur
                ->select(
                    't_barang.lok_spk',
                    't_barang.tipe',
                    't_faktur.nomor_faktur as nomor_faktur', // Mengambil nomor_faktur dari t_faktur
                    't_jual.harga as harga_jual', // Mengambil harga dari t_jual
                    't_barang.status_barang',
                    't_faktur.pembeli as pembeli_faktur', // Alias pembeli dari t_faktur
                    't_faktur.tgl_jual',
                    't_faktur.petugas as petugas_faktur'  // Alias petugas dari t_faktur
                )
                ->whereIn('t_barang.status_barang', [2, 5])
                ->orderBy('t_faktur.tgl_jual', 'desc');

            return DataTables::of($barangs)
                ->addColumn('harga_jual', function ($barang) {
                    return 'Rp. ' . number_format($barang->harga_jual, 0, ',', '.'); // Format harga ke Rupiah
                })
                ->addColumn('nomor_faktur', function ($barang) {
                    $url = route('transaksi-faktur.show', $barang->nomor_faktur);
                    return '<a href="' . $url . '" class="btn btn-info btn-sm" target="_blank">' . $barang->nomor_faktur . '</a>';
                })                
                ->filterColumn('pembeli', function ($query, $keyword) {
                    $query->where('t_faktur.pembeli', 'like', "%{$keyword}%");
                })
                ->filterColumn('petugas', function ($query, $keyword) {
                    $query->where('t_faktur.petugas', 'like', "%{$keyword}%");
                })
                ->filterColumn('nomor_faktur', function ($query, $keyword) {
                    $query->where('t_faktur.nomor_faktur', 'like', "%{$keyword}%");
                })
                ->rawColumns(['nomor_faktur'])
                ->make(true);
        }
    }   

    public function create()
    {   
        $gudangId = optional(Auth::user())->gudang_id;

        return view('pages.transaksi-jual.create',compact('gudangId'));
    }

    public function store(Request $request)
    {
        // 1. Validasi Input Form Awal
        $request->validate([
            'filedata' => 'required|file|mimes:xlsx,xls',
            'tgl_jual' => 'required|date',
            'nomor_faktur' => 'required|string',
            'pembeli' => 'required|string',
            'petugas' => 'required|string',
            'grade' => 'required|string',
        ]);

        // 2. Cek Duplikat Nomor Faktur di Database
        $existingFaktur = Faktur::where('nomor_faktur', $request->input('nomor_faktur'))->exists();
        if ($existingFaktur) {
            return redirect()->back()->with('error', 'Gagal disimpan: Nomor Faktur sudah ada. Harap diganti!')->withInput();
        }

        // Inisialisasi variabel
        $errors = [];
        $totalHargaJual = 0;
        $dataToProcess = []; 
        $processedLokSpkInFile = []; 

        // Ambil gudang_id dari user yang sedang login
        $gudangId = optional(Auth::user())->gudang_id;

        if (!$gudangId) {
            return redirect()->back()->with('error', 'Gagal memvalidasi data. User tidak terasosiasi dengan gudang manapun.');
        }

        // 3. Baca dan Validasi Seluruh Isi File Excel
        $file = $request->file('filedata');
        $data = Excel::toArray([], $file);

        foreach ($data[0] as $index => $row) {
            // Lewati baris header
            if ($index === 0) continue;

            // Pastikan kolom yang dibutuhkan ada isinya
            // if (empty($row[0]) || !isset($row[1])) {
            //     $errors[] = "Baris " . ($index + 1) . ": Data tidak lengkap (Lok SPK atau harga jual kosong).";
            //     continue;
            // }
            
            $lokSpk = $row[0];
            $hargaJual = $row[1] * 1000;

            // Cek duplikat Lok SPK di dalam file yang sama
            if (in_array($lokSpk, $processedLokSpkInFile)) {
                $errors[] = "Baris " . ($index + 1) . ": Lok SPK '$lokSpk' duplikat di dalam file Excel.";
                continue;
            }
            $processedLokSpkInFile[] = $lokSpk;

            // Cari barang di database
            $barang = Barang::where('lok_spk', $lokSpk)->first();

            if (!$barang) {
                $errors[] = "Baris " . ($index + 1) . ": Lok SPK '$lokSpk' tidak ditemukan di database.";
            } elseif ($barang->gudang_id != $gudangId) { 
                $errors[] = "Baris " . ($index + 1) . ": Lok SPK '$lokSpk' tidak terdaftar di gudang Anda.";
            } elseif ($barang->status_barang != 1) { 
                $errors[] = "Baris " . ($index + 1) . ": Lok SPK '$lokSpk' sudah terjual atau statusnya tidak valid.";
            } else {
                $dataToProcess[] = [
                    'lok_spk' => $lokSpk,
                    'harga_jual' => $hargaJual,
                ];
                $totalHargaJual += $hargaJual;
            }
        }

        // 4. TITIK KRITIS: Cek apakah ada error terkumpul
        // Jika ada error, batalkan semua proses dan kembalikan pesan error.
        if (!empty($errors)) {
            return redirect()->back()->with('errors', $errors)->withInput();
        }

        // 5. Cek apakah ada data yang valid untuk diproses setelah validasi
        if (empty($dataToProcess)) {
            return redirect()->back()
                ->with('error', 'Gagal disimpan: Tidak ada data yang valid untuk diproses di dalam file.')
                ->withInput();
        }

        // 6. JIKA SEMUA VALID: Lakukan Operasi Database (Sangat direkomendasikan menggunakan transaksi)
        DB::beginTransaction();
        try {
            $newFaktur = Faktur::create([
                'nomor_faktur' => $request->input('nomor_faktur'),
                'pembeli' => $request->input('pembeli'),
                'tgl_jual' => $request->input('tgl_jual'),
                'petugas' => $request->input('petugas'),
                'grade' => $request->input('grade'),
                'keterangan' => $request->input('keterangan'),
                'total' => $totalHargaJual,
            ]);

            foreach ($dataToProcess as $item) {
                $tipe = Barang::where('lok_spk', $item['lok_spk'])->value('tipe');
                
                $negoan = Negoan::where('tipe', $tipe)
                        ->where('grade', $request->input('grade'))
                        ->where('status', 1)
                        ->orderBy('updated_at', 'desc')
                        ->first();

                Barang::where('lok_spk', $item['lok_spk'])->update([
                    'no_faktur' => $newFaktur->nomor_faktur,
                    'harga_jual' => $item['harga_jual'],
                    'status_barang' => 5,
                ]);

                TransaksiJual::create([
                    'lok_spk' => $item['lok_spk'],
                    'nomor_faktur' => $newFaktur->nomor_faktur,
                    'harga' => $item['harga_jual'],
                    'harga_acc' => $negoan->harga_acc ?? 0,
                ]);
            }
            
            if ($request->hasFile('foto') && $request->filled('nominal')) {
                $path = $request->file('foto')->store('faktur_bukti', 'public');
                
                FakturBukti::create([
                    't_faktur_id' => $newFaktur->id,
                    'nominal' => $request->input('nominal'),
                    'foto' => $path
                ]);
                
                $totalNominal = FakturBukti::where('t_faktur_id', $newFaktur->id)->sum('nominal');
                
                $newFaktur->is_lunas = ($totalNominal >= $newFaktur->total) ? 1 : 0;
                $newFaktur->save(); // Gunakan save() karena ini adalah instance yang sudah ada
            }

            DB::commit(); // Semua query berhasil, simpan perubahan secara permanen

            return redirect()->route('transaksi-faktur.show', ['nomor_faktur' => $newFaktur->nomor_faktur])
                ->with('success', 'Faktur berhasil disimpan. ' . count($dataToProcess) . ' barang berhasil diproses.');

        } catch (\Exception $e) {
            DB::rollBack(); // Terjadi kesalahan saat menyimpan, batalkan semua query

            // Log error $e->getMessage() untuk debugging
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan server saat mencoba menyimpan data. Semua perubahan telah dibatalkan. Pesan: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $transaksi = TransaksiJual::where('id', $id)->firstOrFail();

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

            // Hitung ulang total pada Faktur
            $totalBaru = TransaksiJual::where('nomor_faktur', $nomorFaktur)->sum('harga');
            Faktur::where('nomor_faktur', $nomorFaktur)->update(['total' => $totalBaru]);

            return redirect()->back()->with('success', 'Barang berhasil dihapus');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }


    public function update(Request $request)
    {
        try {
            $validated = $request->validate([
                'id' => 'required|exists:t_jual,id',
                'lok_spk' => 'required|exists:t_jual,lok_spk',
                'harga' => 'required|numeric|min:0',
            ]);
    
            // Gunakan firstOrFail() untuk pencarian berdasarkan 'id'
            $transaksi = TransaksiJual::where('id', $validated['id'])->firstOrFail();
            $transaksi->update(['harga' => $validated['harga']]);
    
            // Update harga_jual pada model Barang
            $barang = $transaksi->barang;
            $barang->update(['harga_jual' => $validated['harga']]);
    
            // Hitung ulang total pada Faktur
            $nomorFaktur = $transaksi->nomor_faktur;
            $totalBaru = TransaksiJual::where('nomor_faktur', $nomorFaktur)->sum('harga');
            Faktur::where('nomor_faktur', $nomorFaktur)->update(['total' => $totalBaru]);
    
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
        $lastFaktur = Faktur::where('nomor_faktur', 'like', "$kodeFaktur-$currentMonthYear-%")
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

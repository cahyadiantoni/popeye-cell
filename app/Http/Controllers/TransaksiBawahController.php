<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\FakturKesimpulan;
use App\Models\KesimpulanBawah;
use App\Models\BuktiTfBawah;
use App\Models\Gudang;
use App\Models\Negoan;
use App\Models\MasterHarga;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Kirim;
use App\Models\FakturBawah;
use App\Models\TransaksiJualBawah;
use App\Models\HistoryEditBarang;
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
                ->whereIn('t_barang.status_barang', [2, 5])
                ->orderBy('t_faktur_bawah.tgl_jual', 'desc');

            return DataTables::of($barangs)
                ->addColumn('harga_jual', function ($barang) {
                    return 'Rp. ' . number_format($barang->harga_jual, 0, ',', '.'); // Format harga ke Rupiah
                })
                ->addColumn('nomor_faktur', function ($barang) {
                    $url = route('transaksi-faktur-bawah.show', $barang->nomor_faktur);
                    return '<a href="' . $url . '" class="btn btn-info btn-sm" target="_blank">' . $barang->nomor_faktur . '</a>';
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
                ->rawColumns(['nomor_faktur'])
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
        // 1. Validasi semua input yang mungkin diterima dari form
        $request->validate([
            'pasted_data' => 'required|string',
            'create_conclusion' => 'required|boolean',
            'fotos'       => 'nullable|array',
            'fotos.*'     => 'nullable|image|max:2048',
            'nominals'    => 'nullable|array',
            'nominals.*'  => 'nullable|numeric|min:1',
        ]);

        // 2. Parsing data mentah dari textarea menjadi baris-baris data
        $pastedData = trim($request->pasted_data);
        $rows = array_filter(explode("\n", $pastedData), 'trim');
        if (empty($rows)) {
            return redirect()->back()->with('error', 'Data yang ditempelkan kosong atau tidak valid.');
        }

        // 3. Ekstrak informasi umum (tanggal, petugas, dll.) dari baris pertama
        $firstRowColumns = str_getcsv(reset($rows), "\t");
        try {
            $dateString = trim($firstRowColumns[0]);
            $bulanIndonesia = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            $bulanInggris   = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            $dateString = str_ireplace($bulanIndonesia, $bulanInggris, $dateString);
            $tglJual = Carbon::parse($dateString)->format('Y-m-d');
            $petugas = trim($firstRowColumns[1]);
            $keterangan = trim($firstRowColumns[2]);
            $grade = trim($firstRowColumns[8]);
            $pembeli = trim($firstRowColumns[12]);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Format tanggal pada baris pertama tidak valid. Gunakan format seperti "14-Mei-2025".');
        }

        // 4. Generate nomor faktur baru yang unik berdasarkan bulan dan tahun
        $kodeFaktur = "BW";
        $currentMonthYear = Carbon::parse($tglJual)->format('my');
        $lastFaktur = FakturBawah::where('nomor_faktur', 'like', "$kodeFaktur-$currentMonthYear-%")
            ->orderByRaw("CAST(SUBSTRING(nomor_faktur, 10, LENGTH(nomor_faktur) - 9) AS UNSIGNED) DESC")
            ->first();
        $newNumber = $lastFaktur ? str_pad((int)substr($lastFaktur->nomor_faktur, -3) + 1, 3, '0', STR_PAD_LEFT) : '001';
        $nomorFakturBaru = "$kodeFaktur-$currentMonthYear-$newNumber";

        // 5. Validasi setiap baris data (cek duplikat, status barang, konsistensi harga, dll.)
        $errors = [];
        $totalHargaJual = 0;
        $validLokSpk = [];
        $processedLokSpk = [];
        $gudangId = optional(Auth::user())->gudang_id;
        if (!$gudangId) {
            return redirect()->back()->with('error', 'Gagal memvalidasi data. User tidak terasosiasi dengan gudang manapun.');
        }
        foreach ($rows as $index => $rowString) {
            $row = str_getcsv($rowString, "\t");
            $lokSpk = $row[3] ?? null;
            $hargaJual = isset($row[5]) ? (int)trim($row[5]) * 1000 : null;
            
            if (!$lokSpk || is_null($hargaJual)) {
                $errors[] = "Baris " . ($index + 1) . ": Data tidak valid (Lok SPK atau harga jual kosong).";
                continue;
            }

            if (in_array($lokSpk, $processedLokSpk)) {
                $errors[] = "Baris " . ($index + 1) . ": Lok SPK '$lokSpk' duplikat di dalam data yang ditempel.";
                continue;
            }

            $processedLokSpk[] = $lokSpk;
            $barang = Barang::where('lok_spk', $lokSpk)->first();

            if (!$barang) {
                $errors[] = "Baris " . ($index + 1) . ": Lok SPK '$lokSpk' tidak ditemukan.";
                continue;
            }

            if ($barang->gudang_id != $gudangId) {
                $errors[] = "Baris " . ($index + 1) . ": Lok SPK '$lokSpk' tidak terdaftar di gudang Anda.";
                continue;
            }

            if (!in_array($barang->status_barang, [1])) {
                if (!($barang->status_barang == 4 && $grade == 'Pengambilan AM')) {
                    $errors[] = "Baris " . ($index + 1) . ": Lok SPK '$lokSpk' memiliki status_barang yang tidak sesuai.";
                    continue;
                }
            }

            // Logika baru untuk cek & update tipe barang
            $tipeBaru = trim($row[6] ?? null);
            if (!empty($tipeBaru)) {
                $tipeBaruNormalisasi = Barang::normalizeString($tipeBaru);
                if ($tipeBaruNormalisasi !== $barang->tipe_normalisasi) {
                    $tipeLama = $barang->tipe;
                    $barang->tipe = $tipeBaru;
                    $barang->save();
                    
                    $pesanHistory = "1. tipe ($tipeLama) menjadi ($tipeBaru)";
                    HistoryEditBarang::create([
                        'lok_spk'   => $barang->lok_spk,
                        'update'    => $pesanHistory,
                        'user_id'   => Auth::id(),
                    ]);
                }
            }

            $tipe = $barang->tipe;
            $tipe_normalisasi = $barang->tipe_normalisasi;

            $hargaSebelumnya = TransaksiJualBawah::join('t_barang', 't_jual_bawah.lok_spk', '=', 't_barang.lok_spk')
                ->join('t_faktur_bawah', 't_faktur_bawah.nomor_faktur', '=', 't_jual_bawah.nomor_faktur')
                ->whereDate('t_faktur_bawah.tgl_jual', $tglJual)
                ->where('t_barang.tipe_normalisasi', $tipe_normalisasi)
                ->where('t_faktur_bawah.grade', $grade)
                ->pluck('t_jual_bawah.harga')
                ->unique();

            if ($hargaSebelumnya->count() > 0 && !$hargaSebelumnya->contains($hargaJual)) {
                $hargaList = $hargaSebelumnya->implode(', ');
                $errors[] = "Baris " . ($index + 1) . ": Harga jual " . ($hargaJual / 1000) . " berbeda dengan transaksi sebelumnya untuk tipe '$tipe', grade '$grade' pada tanggal " . Carbon::parse($tglJual)->format('d-M-Y') . " (harga sebelumnya: ".($hargaList/1000).").";
                continue;
            }
            
            $totalHargaJual += $hargaJual;
            $validLokSpk[] = [ 'lok_spk' => $lokSpk, 'harga_jual' => $hargaJual ];
        }
        if (!empty($errors)) {
            return redirect()->back()->with('errors', $errors)->withInput();
        }
        
        try {
            DB::beginTransaction();

            $faktur = FakturBawah::create([
                'nomor_faktur' => $nomorFakturBaru,
                'pembeli' => $pembeli,
                'tgl_jual' => $tglJual,
                'petugas' => $petugas,
                'grade' => $grade,
                'keterangan' => $keterangan,
                'total' => $totalHargaJual,
            ]);
            foreach ($validLokSpk as $item) {
                $barang = Barang::where('lok_spk', $item['lok_spk'])->first();
                $barang->update(['no_faktur' => $nomorFakturBaru, 'harga_jual' => $item['harga_jual'], 'status_barang' => 5]);
                TransaksiJualBawah::create([
                    'lok_spk' => $item['lok_spk'], 
                    'nomor_faktur' => $nomorFakturBaru, 
                    'harga' => $item['harga_jual'], 
                    'harga_acc' => Negoan::where('tipe', $barang->tipe)->where('grade', $grade)->where('status', 1)->orderBy('updated_at', 'desc')->first()->harga_acc ?? 0
                ]);
            }
            
            $kesimpulan = null;
            if ($request->input('create_conclusion') == '1') {
                
                $bulanTahun = date('my', strtotime($tglJual));
                $prefix = 'K-BW-' . $bulanTahun;
                $count = KesimpulanBawah::where('nomor_kesimpulan', 'like', "$prefix-%")->count();
                $nomor_kesimpulan = "$prefix-" . str_pad($count + 1, 3, '0', STR_PAD_LEFT);

                $kesimpulan = KesimpulanBawah::create([
                    'nomor_kesimpulan' => $nomor_kesimpulan,
                    'tgl_jual' => $tglJual,
                    'total' => $totalHargaJual,
                    'grand_total' => $totalHargaJual,
                    'keterangan' => $keterangan,
                    'is_lunas' => 0,
                ]);
                FakturKesimpulan::create(['kesimpulan_id' => $kesimpulan->id, 'faktur_id' => $faktur->id]);

                $totalNominal = 0;
                if ($request->has('nominals')) {
                    foreach ($request->input('nominals') as $key => $nominal) {
                        if (!empty($nominal) && $request->hasFile("fotos.{$key}")) {
                            $path = $request->file("fotos.{$key}")->store('bukti_transfer_kesimpulan', 'public');
                            BuktiTfBawah::create([
                                'kesimpulan_id' => $kesimpulan->id,
                                'nominal' => $nominal,
                                'foto' => $path,
                                'keterangan' => 'Transfer - Bukti ' . ($key + 1),
                            ]);
                            $totalNominal += $nominal;
                        }
                    }
                }

                if ($totalNominal >= $kesimpulan->grand_total && $kesimpulan->grand_total > 0) {
                    $kesimpulan->is_lunas = 1;
                    $kesimpulan->save();
                }
            }
            
            DB::commit();

            if ($kesimpulan) {
                return redirect()->route('transaksi-kesimpulan.show', ['kesimpulan_id' => $kesimpulan->id])
                    ->with('success', 'Faktur dan Kesimpulan berhasil dibuat!');
            } else {
                return redirect()->route('transaksi-faktur-bawah.show', ['nomor_faktur' => $nomorFakturBaru])
                    ->with('success', 'Faktur berhasil disimpan.');
            }

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal menyimpan data: ' . $e->getMessage());
        }
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

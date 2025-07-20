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
use App\Models\HistoryEditFakturAtas;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
            'potongan_kondisi' => 'nullable|numeric|min:0',
            'diskon' => 'nullable|numeric|min:0|max:100',
            'foto' => 'nullable|image',
            'nominal' => 'nullable|numeric',
        ]);

        // 2. Cek Duplikat Nomor Faktur di Database
        $existingFaktur = Faktur::where('nomor_faktur', $request->input('nomor_faktur'))->exists();
        if ($existingFaktur) {
            return redirect()->back()->with('error', 'Gagal disimpan: Nomor Faktur sudah ada. Harap diganti!')->withInput();
        }

        // Inisialisasi variabel
        $errors = [];
        $totalHargaJual = 0; // Ini akan menjadi subtotal sebelum diskon/potongan
        $dataToProcess = [];
        $processedLokSpkInFile = [];

        $gudangId = optional(Auth::user())->gudang_id;

        if (!$gudangId) {
            return redirect()->back()->with('error', 'Gagal memvalidasi data. User tidak terasosiasi dengan gudang manapun.');
        }

        // 3. Baca dan Validasi Seluruh Isi File Excel
        $file = $request->file('filedata');
        $data = Excel::toArray([], $file);

        foreach ($data[0] as $index => $row) {
            if ($index === 0) continue; // Lewati header

            if (empty($row[0]) || !isset($row[1])) {
                $errors[] = "Baris " . ($index + 1) . ": Data tidak lengkap (Lok SPK atau harga jual kosong).";
                continue;
            }

            $lokSpk = $row[0];
            $hargaJual = $row[1] * 1000;

            if (in_array($lokSpk, $processedLokSpkInFile)) {
                $errors[] = "Baris " . ($index + 1) . ": Lok SPK '$lokSpk' duplikat di dalam file Excel.";
                continue;
            }
            $processedLokSpkInFile[] = $lokSpk;

            $barang = Barang::where('lok_spk', $lokSpk)->first();

            if (!$barang) {
                $errors[] = "Baris " . ($index + 1) . ": Lok SPK '$lokSpk' tidak ditemukan di database.";
            } elseif ($barang->gudang_id != $gudangId) {
                $errors[] = "Baris " . ($index + 1) . ": Lok SPK '$lokSpk' tidak terdaftar di gudang Anda.";
            } elseif ($barang->status_barang != 1) {
                $errors[] = "Baris " . ($index + 1) . ": Lok SPK '$lokSpk' sudah terjual atau statusnya tidak valid.";
            } else {
                $dataToProcess[] = ['lok_spk' => $lokSpk, 'harga_jual' => $hargaJual];
                $totalHargaJual += $hargaJual; // Akumulasi subtotal
            }
        }

        // 4. TITIK KRITIS: Cek apakah ada error terkumpul
        if (!empty($errors)) {
            return redirect()->back()->with('errors', $errors)->withInput();
        }

        // 5. Cek apakah ada data yang valid untuk diproses
        if (empty($dataToProcess)) {
            return redirect()->back()->with('error', 'Gagal disimpan: Tidak ada data yang valid untuk diproses di dalam file.')->withInput();
        }

        // PERHITUNGAN TOTAL AKHIR
        $potonganKondisi = $request->input('potongan_kondisi', 0);
        $diskonPersen = $request->input('diskon', 0);

        $hargaSetelahPotongan = $totalHargaJual - $potonganKondisi;

        $diskonAmount = ($hargaSetelahPotongan * $diskonPersen) / 100;

        $finalTotal = $hargaSetelahPotongan - $diskonAmount;

        $finalTotal = ceil(max(0, $finalTotal));

        // 6. JIKA SEMUA VALID: Lakukan Operasi Database
        DB::beginTransaction();
        try {
            $newFaktur = Faktur::create([
                'nomor_faktur' => $request->input('nomor_faktur'),
                'pembeli' => $request->input('pembeli'),
                'tgl_jual' => $request->input('tgl_jual'),
                'petugas' => $request->input('petugas'),
                'grade' => $request->input('grade'),
                'keterangan' => $request->input('keterangan'),
                'total' => $finalTotal,
                'potongan_kondisi' => $potonganKondisi ?? 0,
                'diskon' => $diskonPersen ?? 0,
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
                $newFaktur->save();
            }

            DB::commit();

            return redirect()->route('transaksi-faktur.show', ['nomor_faktur' => $newFaktur->nomor_faktur])
                ->with('success', 'Faktur berhasil disimpan. ' . count($dataToProcess) . ' barang berhasil diproses.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'Terjadi kesalahan server saat mencoba menyimpan data. Pesan: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy(TransaksiJual $transaksi_jual)
    {
        try {
            DB::transaction(function () use ($transaksi_jual) {
                // 1. Simpan info penting sebelum dihapus
                $lok_spk = $transaksi_jual->lok_spk;
                $nomorFaktur = $transaksi_jual->nomor_faktur;

                // --> TAMBAHAN: Catat riwayat perubahan faktur
                HistoryEditFakturAtas::create([
                    'faktur_id' => $transaksi_jual->faktur->id, // Ambil ID faktur dari relasi
                    'update'    => "Menghapus barang (LOK SPK: {$lok_spk}) dari faktur.",
                    'user_id'   => auth()->id(),
                ]);

                // 2. Update status barang terkait menjadi tersedia kembali
                Barang::where('lok_spk', $lok_spk)->update([
                    'status_barang' => 1,
                    'no_faktur' => null,
                    'harga_jual' => null,
                ]);

                // 3. Hapus transaksi dari t_jual
                $transaksi_jual->delete();

                // 4. Hitung ulang total pada Faktur
                $totalBaru = TransaksiJual::where('nomor_faktur', $nomorFaktur)->sum('harga');
                Faktur::where('nomor_faktur', $nomorFaktur)->update(['total' => $totalBaru]);
            });

            return redirect()->back()->with('success', 'Barang berhasil dihapus dari transaksi.');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function update(Request $request, TransaksiJual $transaksi_jual)
    {
        // 1. Validasi input dari form
        $validated = $request->validate([
            'lok_spk' => 'required|string|exists:t_barang,lok_spk',
            'harga' => 'required|numeric|min:0',
        ]);

        try {
            DB::transaction(function () use ($request, $transaksi_jual, $validated) {
                // 2. Ambil data dan kalkulasi harga baru
                $lok_spk_lama = $transaksi_jual->lok_spk;
                $nomor_faktur_lama = $transaksi_jual->nomor_faktur;
                $lok_spk_baru = $validated['lok_spk'];
                $hargaBaru = $validated['harga']; 
                $historyMessage = ''; // Siapkan variabel untuk pesan histori

                $hargaLama = $transaksi_jual->harga;

                // 3. Cek apakah lok_spk diubah atau tidak
                if ($lok_spk_lama !== $lok_spk_baru) {
                    // a. Cek status barang baru
                    $barang_baru = Barang::where('lok_spk', $lok_spk_baru)->first();
                    if ($barang_baru->status_barang == 2) {
                        throw ValidationException::withMessages([
                           'lok_spk' => 'LOK SPK baru sudah digunakan di transaksi lain.'
                        ]);
                    }

                    // b. Update data barang LAMA (menjadi tersedia kembali)
                    Barang::where('lok_spk', $lok_spk_lama)->update([
                        'status_barang' => 1,
                        'harga_jual' => null,
                        'no_faktur' => null
                    ]);

                    // c. Update data barang BARU (menjadi terjual)
                    $barang_baru->update([
                        'status_barang' => 2,
                        'harga_jual' => $hargaBaru, // Gunakan harga baru
                        'no_faktur' => $nomor_faktur_lama
                    ]);
                    
                    // d. Buat pesan histori untuk perubahan LOK SPK
                    $historyMessage = "Mengganti barang dari LOK SPK '{$lok_spk_lama}' ke '{$lok_spk_baru}' dengan harga baru Rp " . number_format($hargaBaru) . ".";

                } else {
                    // --- LOGIKA JIKA HANYA HARGA YANG BERUBAH ---
                    $transaksi_jual->barang()->update(['harga_jual' => $hargaBaru]); // Gunakan harga baru

                    // Buat pesan histori untuk perubahan harga saja
                    $historyMessage = "Mengubah harga untuk LOK SPK '{$lok_spk_lama}' dari Rp " . number_format($hargaLama) . " menjadi Rp " . number_format($hargaBaru) . ".";
                }

                // 4. Update data di tabel transaksi itu sendiri (t_jual)
                $transaksi_jual->update([
                    'lok_spk' => $lok_spk_baru,
                    'harga' => $hargaBaru // Gunakan harga baru
                ]);

                // 5. Catat riwayat perubahan faktur
                HistoryEditFakturAtas::create([
                    'faktur_id' => $transaksi_jual->faktur->id,
                    'update'    => $historyMessage,
                    'user_id'   => auth()->id(),
                ]);

                // 6. Hitung ulang total pada Faktur
                $totalBaru = TransaksiJual::where('nomor_faktur', $nomor_faktur_lama)->sum('harga');
                Faktur::where('nomor_faktur', $nomor_faktur_lama)->update(['total' => $totalBaru]);
            });

            return redirect()->back()->with('success', 'Transaksi berhasil diupdate!');

        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
    
    public function getSuggestNoFak(Request $request)
    {
        $kodeFaktur = $request->kode_faktur;
        $tglJual = $request->tgl_jual ? Carbon::parse($request->tgl_jual) : Carbon::now();
        $currentMonthYear = $tglJual->format('my');

        $lastFaktur = Faktur::where('nomor_faktur', 'like', "$kodeFaktur-$currentMonthYear-%")
            ->orderByRaw('LENGTH(nomor_faktur) DESC, nomor_faktur DESC')
            ->first();

        if ($lastFaktur) {
            preg_match('/-(\d+)$/', $lastFaktur->nomor_faktur, $matches);
            $lastNumber = isset($matches[1]) ? (int) $matches[1] : 0;
            $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '001';
        }

        $suggestedNoFak = "$kodeFaktur-$currentMonthYear-$newNumber";

        return response()->json(['suggested_no_fak' => $suggestedNoFak]);
    }

    public function addbarang(Request $request)
    {
        // 1. Validasi input dari form modal
        $validated = $request->validate([
            'lok_spk' => 'required|string',
            'harga' => 'required|numeric|min:0',
            'nomor_faktur' => 'required|exists:t_faktur,nomor_faktur',
            'grade' => 'required',
        ]);

        // Menggunakan DB::transaction untuk memastikan integritas data
        try {
            DB::transaction(function () use ($validated) {
                $lokSpk = $validated['lok_spk'];
                $hargaJual = $validated['harga'] * 1000;
                $nomorFaktur = $validated['nomor_faktur'];
                $grade = $validated['grade'];

                // 2. Cek apakah barang ada dan statusnya valid
                $barang = Barang::where('lok_spk', $lokSpk)->first();

                if (!$barang) {
                    // Jika barang tidak ditemukan, batalkan transaksi
                    throw new \Exception("LOK SPK '$lokSpk' tidak ditemukan di database.");
                }

                if (!in_array($barang->status_barang, [0, 1])) {
                    // Jika barang sudah terjual atau status lain, batalkan
                    throw new \Exception("LOK SPK '$lokSpk' sudah terjual atau statusnya tidak valid.");
                }
                
                // 3. Cek duplikat di transaksi untuk faktur yang sama
                $isExist = TransaksiJual::where('lok_spk', $lokSpk)->where('nomor_faktur', $nomorFaktur)->exists();
                if($isExist) {
                    throw new \Exception("LOK SPK '$lokSpk' sudah ada di faktur ini.");
                }

                // 4. Cari harga negoan
                $negoan = Negoan::where('tipe', $barang->tipe)
                    ->where('grade', $grade)
                    ->where('status', 1)
                    ->orderBy('updated_at', 'desc')
                    ->first();

                // 5. Buat entri baru di TransaksiJual
                TransaksiJual::create([
                    'lok_spk' => $lokSpk,
                    'nomor_faktur' => $nomorFaktur,
                    'harga' => $hargaJual,
                    'harga_acc' => $negoan->harga_acc ?? 0,
                ]);

                // 6. Update data di tabel Barang
                $barang->update([
                    'status_barang' => 2, // Ubah status menjadi terjual
                    'no_faktur' => $nomorFaktur,
                    'harga_jual' => $hargaJual,
                ]);

                // 7. Ambil objek faktur untuk mendapatkan ID dan update total
                $faktur = Faktur::where('nomor_faktur', $nomorFaktur)->first();

                // 8. Catat riwayat perubahan faktur
                HistoryEditFakturAtas::create([
                    'faktur_id' => $faktur->id,
                    'update'    => "Menambahkan barang baru (LOK SPK: {$lokSpk}) ke faktur dengan harga Rp " . number_format($hargaJual) . ".",
                    'user_id'   => auth()->id(),
                ]);

                // 9. Update total harga di Faktur
                $faktur->total += $hargaJual;
                $faktur->save();
            });

            return redirect()->back()->with('success', 'Barang berhasil ditambahkan ke faktur.');

        } catch (\Exception $e) {
            // Tangkap semua error (baik dari validasi manual atau masalah database)
            return redirect()->back()->with('error', 'Gagal menambahkan barang: ' . $e->getMessage())->withInput();
        }
    }

}

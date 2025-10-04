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
use App\Models\HistoryEditFakturBawah;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;
use App\Services\SettingsService; // Untuk mengambil setting

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

    public function create(SettingsService $settingsService)
    {   
        // 1. Ambil setting waktu tutup. 
        //    Kita set default '23:59' (selalu buka) jika setting tidak ada.
        $waktuTutupString = $settingsService->get('WAKTU_TUTUP_BAWAH', '23:59');

        // 2. Parse string waktu (misal "17:00") ke objek Carbon.
        //    Carbon::parse() akan otomatis menggunakan tanggal hari ini.
        $waktuTutup = Carbon::parse($waktuTutupString);
        $waktuSekarang = Carbon::now();

        // 3. Cek apakah waktu sekarang SUDAH LEWAT atau SAMA DENGAN waktu tutup
        //    gte() = Greater Than or Equal (Lebih besar atau sama dengan)
        if ($waktuSekarang->gte($waktuTutup)) {
            
            // Jika sudah tutup, arahkan ke view 'transaksi-tutup'
            return view('pages.transaksi.transaksi-tutup', [
                'waktuTutup' => $waktuTutup->format('H:i') // Kirim data jam (misal "17:00")
            ]);
        }

        $gudangId = optional(Auth::user())->gudang_id;

        return view('pages.transaksi-jual-bawah.create',compact('gudangId'));
    }

    public function store(Request $request, SettingsService $settingsService)
    {
        // 1. Validasi input awal
        $request->validate([
            'pasted_data' => 'required|string',
            'create_conclusion' => 'required|boolean',
            'fotos'       => 'nullable|array',
            'fotos.*'     => 'nullable|image',
            'nominals'    => 'nullable|array',
            'nominals.*'  => 'nullable|numeric|min:1',
        ]);

        // 2. Parsing data mentah
        $pastedData = trim($request->pasted_data);
        $rows = array_filter(explode("\n", $pastedData), 'trim');
        if (empty($rows)) {
            return redirect()->back()->with('error', 'Data yang ditempelkan kosong atau tidak valid.');
        }

        // 3. Ekstrak informasi umum
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

        // --- PERUBAHAN 2: Blok validasi tanggal yang diperbarui ---
        try {
            // Ambil setting batas hari. TIDAK ADA DEFAULT, jadi hasilnya null jika tidak ada.
            $hariBatas = $settingsService->get('HARI_INPUT_FAKTUR_SEBELUM'); 
            
            $tglJualCarbon = Carbon::parse($tglJual)->startOfDay();
            $hariIni = Carbon::today();

            // Aturan 1 (SELALU AKTIF): Tanggal jual tidak boleh di masa depan
            if ($tglJualCarbon->isFuture()) {
                throw new \Exception("Tanggal jual ({$tglJualCarbon->translatedFormat('d F Y')}) tidak boleh melebihi hari ini.");
            }

            // Aturan 2 (KONDISIONAL): Cek batas hari ke belakang HANYA JIKA settingnya ada dan valid
            if ($hariBatas !== null && is_numeric($hariBatas) && $hariBatas >= 0) {
                $hariBatas = (int) $hariBatas; // Konversi ke integer untuk keamanan
                $tanggalTerlama = $hariIni->copy()->subDays($hariBatas);
                
                if ($tglJualCarbon->lt($tanggalTerlama)) {
                    throw new \Exception("Tanggal jual ({$tglJualCarbon->translatedFormat('d F Y')}) sudah terlalu lama. Batas maksimal adalah {$hariBatas} hari ke belakang (paling awal: {$tanggalTerlama->translatedFormat('d F Y')}).");
                }
            }
            // Jika $hariBatas adalah null atau tidak valid, blok 'if' ini dilewati dan tidak ada validasi batas hari ke belakang.

        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
        // --- Akhir dari blok validasi tanggal ---

        // 4. Generate nomor faktur baru
        $kodeFaktur = "BW";
        $currentMonthYear = Carbon::parse($tglJual)->format('my');
        
        $lastFaktur = FakturBawah::where('nomor_faktur', 'like', "$kodeFaktur-$currentMonthYear-%")
            ->orderByRaw("CAST(SUBSTRING(nomor_faktur, 9) AS UNSIGNED) DESC")
            ->first();
        
        if ($lastFaktur) {
            $lastNumber = (int)substr($lastFaktur->nomor_faktur, 8);
            $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '001';
        }
        
        $nomorFakturBaru = "$kodeFaktur-$currentMonthYear-$newNumber";

        // 5. Validasi setiap baris data
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
            $hargaJual = isset($row[5]) ? (float)str_replace(',', '.', trim($row[5])) * 1000 : null;
            
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
            $tipeBaru = trim($row[6] ?? null);
            if (!empty($tipeBaru)) {
                $tipeBaruNormalisasi = Barang::normalizeString($tipeBaru);
                if ($tipeBaruNormalisasi !== $barang->tipe_normalisasi) {
                    $tipeLama = $barang->tipe;
                    $barang->tipe = $tipeBaru;
                    $barang->save();
                    HistoryEditBarang::create([
                        'lok_spk'   => $barang->lok_spk,
                        'update'    => "1. tipe ($tipeLama) menjadi ($tipeBaru)",
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
        
        // 6. Proses penyimpanan ke database
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

                $total = (float) $totalHargaJual;
                $potongan_kondisi = (float) $request->input('potongan_kondisi', 0);
                $diskon = (float) $request->input('diskon', 0);
                $setelah_potongan = $total - $potongan_kondisi;
                $setelah_diskon = $setelah_potongan - ($setelah_potongan * ($diskon / 100));
                $grand_total = max($setelah_diskon, 0);

                $kesimpulan = KesimpulanBawah::create([
                    'nomor_kesimpulan' => $nomor_kesimpulan,
                    'tgl_jual' => $tglJual,
                    'total' => $totalHargaJual,
                    'grand_total' => $grand_total,
                    'potongan_kondisi' => $request->input('potongan_kondisi') ?? 0,
                    'diskon' => $request->input('diskon') ?? 0,
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
    
    public function addbarang(Request $request)
    {
        $validated = $request->validate([
            'lok_spk' => 'required|string',
            'harga' => 'required|numeric|min:0',
            'nomor_faktur' => 'required|exists:t_faktur_bawah,nomor_faktur',
            'grade' => 'required',
        ]);

        try {
            DB::transaction(function () use ($validated) {
                $barang = Barang::where('lok_spk', $validated['lok_spk'])->firstOrFail();
                
                if (!in_array($barang->status_barang, [0, 1])) {
                    throw new \Exception("LOK SPK '{$validated['lok_spk']}' sudah terjual atau statusnya tidak valid.");
                }

                $isExist = TransaksiJualBawah::where('lok_spk', $validated['lok_spk'])->where('nomor_faktur', $validated['nomor_faktur'])->exists();
                if($isExist) {
                    throw new \Exception("LOK SPK '{$validated['lok_spk']}' sudah ada di faktur ini.");
                }

                TransaksiJualBawah::create([
                    'lok_spk' => $validated['lok_spk'],
                    'nomor_faktur' => $validated['nomor_faktur'],
                    'harga' => $validated['harga'] * 1000,
                    'harga_acc' => 0, // Sesuaikan jika perlu
                ]);

                $barang->update([
                    'status_barang' => 2, 
                    'no_faktur' => $validated['nomor_faktur'],
                    'harga_jual' => $validated['harga'],
                ]);
                
                $faktur = FakturBawah::where('nomor_faktur', $validated['nomor_faktur'])->first();
                
                HistoryEditFakturBawah::create([
                    'faktur_id' => $faktur->id,
                    'update'    => "Menambahkan barang baru (LOK SPK: {$validated['lok_spk']}) ke faktur dengan harga Rp " . number_format($validated['harga']) . ".",
                    'user_id'   => auth()->id(),
                ]);

                $faktur->total += $validated['harga'];
                $faktur->save();
            });

            return redirect()->back()->with('success', 'Barang berhasil ditambahkan ke faktur.');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menambahkan barang: ' . $e->getMessage())->withInput();
        }
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'lok_spk' => 'required|string|exists:t_barang,lok_spk',
            'harga' => 'required|numeric|min:0',
        ]);

        try {
            DB::transaction(function () use ($request, $id, $validated) {
                $transaksi_jual = TransaksiJualBawah::with('faktur')->findOrFail($id);
                $lok_spk_lama = $transaksi_jual->lok_spk;
                $nomor_faktur = $transaksi_jual->nomor_faktur;
                $lok_spk_baru = $validated['lok_spk'];
                $hargaBaru = $validated['harga'];
                $historyMessage = '';

                if ($lok_spk_lama !== $lok_spk_baru) {
                    $barang_baru = Barang::where('lok_spk', $lok_spk_baru)->first();
                    if ($barang_baru->status_barang != 1) {
                        throw ValidationException::withMessages(['lok_spk' => 'LOK SPK baru sudah digunakan atau statusnya tidak valid.']);
                    }

                    Barang::where('lok_spk', $lok_spk_lama)->update(['status_barang' => 1, 'harga_jual' => null, 'no_faktur' => null]);
                    $barang_baru->update(['status_barang' => 2, 'harga_jual' => $hargaBaru, 'no_faktur' => $nomor_faktur]);
                    $historyMessage = "Mengganti barang dari LOK SPK '{$lok_spk_lama}' ke '{$lok_spk_baru}' dengan harga baru Rp " . number_format($hargaBaru) . ".";
                } else {
                    $transaksi_jual->barang()->update(['harga_jual' => $hargaBaru]);
                    $historyMessage = "Mengubah harga untuk LOK SPK '{$lok_spk_lama}' dari Rp " . number_format($transaksi_jual->harga) . " menjadi Rp " . number_format($hargaBaru) . ".";
                }

                $transaksi_jual->update(['lok_spk' => $lok_spk_baru, 'harga' => $hargaBaru]);

                HistoryEditFakturBawah::create([
                    'faktur_id' => $transaksi_jual->faktur->id,
                    'update'    => $historyMessage,
                    'user_id'   => auth()->id(),
                ]);

                $totalBaru = TransaksiJualBawah::where('nomor_faktur', $nomor_faktur)->sum('harga');
                FakturBawah::where('nomor_faktur', $nomor_faktur)->update(['total' => $totalBaru]);
            });

            return redirect()->back()->with('success', 'Transaksi berhasil diupdate!');

        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            DB::transaction(function () use ($id) {
                $transaksi_jual = TransaksiJualBawah::with('faktur')->findOrFail($id);
                $lok_spk = $transaksi_jual->lok_spk;
                $nomorFaktur = $transaksi_jual->nomor_faktur;

                HistoryEditFakturBawah::create([
                    'faktur_id' => $transaksi_jual->faktur->id,
                    'update'    => "Menghapus barang (LOK SPK: {$lok_spk}) dari faktur.",
                    'user_id'   => auth()->id(),
                ]);

                Barang::where('lok_spk', $lok_spk)->update(['status_barang' => 1, 'no_faktur' => null, 'harga_jual' => null]);
                $transaksi_jual->delete();

                $totalBaru = TransaksiJualBawah::where('nomor_faktur', $nomorFaktur)->sum('harga');
                FakturBawah::where('nomor_faktur', $nomorFaktur)->update(['total' => $totalBaru]);
            });

            return redirect()->back()->with('success', 'Barang berhasil dihapus dari transaksi.');
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

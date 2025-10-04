<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\Gudang;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Kirim;
use App\Models\FakturOnline;
use App\Models\TransaksiJualOnline;
use App\Models\HistoryEditFakturOnline;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use App\Services\SettingsService; // Untuk mengambil setting

class TransaksiOnlineController extends Controller
{
    public function index()
    {
        $allgudangs = Gudang::all();
        return view('pages.transaksi-jual-online.index', compact('allgudangs'));
    }

    public function getData(Request $request)
    {
        if ($request->ajax()) {
            $barangs = Barang::join('t_jual_online', 't_barang.lok_spk', '=', 't_jual_online.lok_spk')
                ->join('t_faktur_online', 't_jual_online.faktur_online_id', '=', 't_faktur_online.id')
                ->select(
                    't_barang.lok_spk',
                    't_barang.tipe',
                    't_jual_online.harga as harga_jual', // Gunakan harga dari t_jual_online
                    't_barang.status_barang',
                    't_faktur_online.id as id_faktur_online',
                    't_faktur_online.title as title_faktur',
                    't_jual_online.invoice as invoice',
                    't_faktur_online.toko as toko_faktur',
                    't_faktur_online.tgl_jual',
                    't_faktur_online.petugas as petugas_faktur'
                )
                ->whereIn('t_barang.status_barang', [2, 5])
                ->orderBy('t_faktur_online.tgl_jual', 'desc');
    
            return DataTables::of($barangs)
                ->addColumn('harga_jual', function ($barang) {
                    return 'Rp. ' . number_format($barang->harga_jual, 0, ',', '.');
                })
                ->addColumn('title_faktur', function ($barang) {
                    $url = route('transaksi-faktur-online.show', $barang->id_faktur_online);
                    return '<a href="' . $url . '" class="btn btn-info btn-sm" target="_blank" rel="noopener noreferrer">' . $barang->title_faktur . '</a>';
                })
                ->filterColumn('invoice', function ($query, $keyword) {
                    $query->where('t_jual_online.invoice', 'like', "%{$keyword}%");
                })
                ->filterColumn('title', function ($query, $keyword) {
                    $query->where('t_faktur_online.title', 'like', "%{$keyword}%");
                })
                ->filterColumn('toko', function ($query, $keyword) {
                    $query->where('t_faktur_online.toko', 'like', "%{$keyword}%");
                })
                ->filterColumn('petugas', function ($query, $keyword) {
                    $query->where('t_faktur_online.petugas', 'like', "%{$keyword}%");
                })
                ->rawColumns(['title_faktur'])
                ->make(true);
        }
    }    

    public function create(SettingsService $settingsService)
    {
        // 1. Ambil setting waktu tutup. 
        //    Kita set default '23:59' (selalu buka) jika setting tidak ada.
        $waktuTutupString = $settingsService->get('WAKTU_TUTUP_ATAS', '23:59');

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

        return view('pages.transaksi-jual-online.create');
    }

    // --- PERUBAHAN 1: Tambahkan SettingsService ke dalam parameter method ---
    public function store(Request $request, SettingsService $settingsService)
    {
        // TAHAP 1: VALIDASI INPUT & PRASYARAT AWAL
        $request->validate([
            'filedata' => 'required|file|mimes:xlsx,xls',
            'tgl_jual' => 'required|date',
            'title' => 'required|string',
            'toko' => 'required|string',
            'petugas' => 'required|string',
            'grade' => 'required|string',
        ]);
        
        // --- PERUBAHAN 2: Tambahkan blok validasi tanggal di sini ---
        try {
            // Ambil setting batas hari. Hasilnya null jika tidak ada.
            $hariBatas = $settingsService->get('HARI_INPUT_FAKTUR_SEBELUM'); 
            
            // Konversi tanggal jual dari input ke objek Carbon
            $tglJualCarbon = Carbon::parse($request->input('tgl_jual'))->startOfDay();
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
            // Jika $hariBatas adalah null atau tidak valid, blok 'if' ini dilewati.

        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
        // --- Akhir dari blok validasi tanggal ---


        // Inisialisasi variabel
        $errors = [];
        $totalHargaJual = 0;
        $dataToProcess = []; 
        $processedLokSpkInFile = [];

        $gudangId = optional(Auth::user())->gudang_id;
        if (!$gudangId) {
            return redirect()->back()->with('error', 'Gagal memvalidasi data. User tidak terasosiasi dengan gudang manapun.');
        }

        // 2. Baca dan Validasi Seluruh Isi File Excel
        $file = $request->file('filedata');
        $data = Excel::toArray([], $file);

        foreach ($data[0] as $index => $row) {
            if ($index === 0) continue;

            if (!isset($row[0], $row[1], $row[2], $row[3])) {
                $errors[] = "Baris " . ($index + 1) . ": Data tidak lengkap. Pastikan kolom Invoice, Lok SPK, Harga Jual, dan PJ terisi.";
                continue;
            }
            
            $invoice = $row[0];
            $lokSpk = $row[1];
            $hargaJual = $row[2] * 1000;
            $pj = $row[3] * 1000;

            if (in_array($lokSpk, $processedLokSpkInFile)) {
                $errors[] = "Baris " . ($index + 1) . ": Lok SPK '$lokSpk' duplikat di dalam file Excel.";
                continue;
            }
            $processedLokSpkInFile[] = $lokSpk;

            $barang = Barang::where('lok_spk', $lokSpk)->first();

            if ($barang) {
                if ($barang->gudang_id != $gudangId) {
                    $errors[] = "Baris " . ($index + 1) . ": Lok SPK '$lokSpk' tidak terdaftar di gudang Anda.";
                } elseif ($barang->status_barang != 1) {
                    $errors[] = "Baris " . ($index + 1) . ": Lok SPK '$lokSpk' sudah terjual atau statusnya tidak valid (" . $barang->status_barang . ").";
                } else {
                    $totalHargaJual += $hargaJual;
                    $dataToProcess[] = [
                        'invoice' => $invoice,
                        'lok_spk' => $lokSpk,
                        'harga_jual' => $hargaJual,
                        'pj' => $pj,
                    ];
                }
            } else {
                $errors[] = "Baris " . ($index + 1) . ": Lok SPK '$lokSpk' tidak ditemukan di database.";
            }
        }

        // 3. TAHAP KRITIS: Gerbang Keputusan
        if (!empty($errors)) {
            return redirect()->back()->with('errors', $errors)->withInput();
        }

        // 4. Pastikan ada data untuk diproses
        if (empty($dataToProcess)) {
            return redirect()->back()
                ->with('error', 'Gagal: Tidak ada data valid yang dapat diproses dari file yang diunggah.')
                ->withInput();
        }

        // 5. JIKA LOLOS SEMUA VALIDASI: Lakukan Operasi Database
        DB::beginTransaction();
        try {
            $fakturOnline = FakturOnline::create([
                'title' => $request->input('title'),
                'toko' => $request->input('toko'),
                'tgl_jual' => $request->input('tgl_jual'),
                'petugas' => $request->input('petugas'),
                'grade' => $request->input('grade'),
                'keterangan' => $request->input('keterangan'),
                'total' => $totalHargaJual,
            ]);

            $fakturOnlineId = $fakturOnline->id;

            foreach ($dataToProcess as $item) {
                Barang::where('lok_spk', $item['lok_spk'])->update([
                    'no_faktur' => $fakturOnlineId,
                    'harga_jual' => $item['harga_jual'],
                    'status_barang' => 5,
                ]);

                TransaksiJualOnline::create([
                    'invoice' => $item['invoice'],
                    'lok_spk' => $item['lok_spk'],
                    'faktur_online_id' => $fakturOnlineId,
                    'harga' => $item['harga_jual'],
                    'pj' => $item['pj'],
                ]);
            }

            DB::commit();

            return redirect()->route('transaksi-faktur-online.show', ['nomor_faktur' => $fakturOnlineId])
                ->with('success', 'Faktur berhasil disimpan. ' . count($dataToProcess) . ' barang berhasil diproses.');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'Terjadi kesalahan pada server saat menyimpan data. Semua perubahan telah dibatalkan.')
                ->withInput();
        }
    }

    public function addbarang(Request $request)
    {
        $validated = $request->validate([
            'lok_spk' => 'required|string',
            'harga' => 'required|numeric|min:0',
            'pj' => 'required|numeric|min:0',
            'invoice' => 'required|string',
            'faktur_online_id' => 'required|exists:t_faktur_online,id',
        ]);

        try {
            DB::transaction(function () use ($validated) {
                $hargaJual = $validated['harga'] * 1000;
                $hargaPj = $validated['pj'] * 1000;

                $barang = Barang::where('lok_spk', $validated['lok_spk'])->firstOrFail();
                
                if ($barang->status_barang != 1) {
                    throw new \Exception("LOK SPK '{$validated['lok_spk']}' sudah terjual atau statusnya tidak valid.");
                }

                $isExist = TransaksiJualOnline::where('lok_spk', $validated['lok_spk'])->where('faktur_online_id', $validated['faktur_online_id'])->exists();
                if($isExist) {
                    throw new \Exception("LOK SPK '{$validated['lok_spk']}' sudah ada di faktur ini.");
                }

                TransaksiJualOnline::create([
                    'lok_spk' => $validated['lok_spk'],
                    'invoice' => $validated['invoice'],
                    'faktur_online_id' => $validated['faktur_online_id'],
                    'harga' => $hargaJual,
                    'pj' => $hargaPj,
                ]);

                $barang->update(['status_barang' => 2, 'no_faktur' => $validated['faktur_online_id'], 'harga_jual' => $hargaJual]);
                
                HistoryEditFakturOnline::create([
                    'faktur_id' => $validated['faktur_online_id'],
                    'update'    => "Menambahkan barang (LOK SPK: {$validated['lok_spk']}) dengan harga Rp " . number_format($hargaJual),
                    'user_id'   => auth()->id(),
                ]);

                $faktur = FakturOnline::find($validated['faktur_online_id']);
                $faktur->total += $hargaJual;
                $faktur->save();
            });

            return redirect()->back()->with('success', 'Barang berhasil ditambahkan ke faktur.');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal: ' . $e->getMessage())->withInput();
        }
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'lok_spk' => 'required|string|exists:t_barang,lok_spk',
            'harga' => 'required|numeric|min:0',
            'pj' => 'required|numeric|min:0',
            'invoice' => 'required|string',
        ]);

        try {
            DB::transaction(function () use ($id, $validated) {
                $transaksi = TransaksiJualOnline::with('faktur')->findOrFail($id);
                $historyMessage = [];

                if ($transaksi->lok_spk !== $validated['lok_spk']) {
                    $barangBaru = Barang::where('lok_spk', $validated['lok_spk'])->first();
                    if ($barangBaru->status_barang != 1) {
                        throw ValidationException::withMessages(['lok_spk' => 'LOK SPK baru sudah digunakan atau statusnya tidak valid.']);
                    }
                    Barang::where('lok_spk', $transaksi->lok_spk)->update(['status_barang' => 1, 'harga_jual' => null, 'no_faktur' => null]);
                    $barangBaru->update(['status_barang' => 2, 'harga_jual' => $validated['harga'], 'no_faktur' => $transaksi->faktur_online_id]);
                    $historyMessage[] = "Barang diubah dari '{$transaksi->lok_spk}' menjadi '{$validated['lok_spk']}'";
                }

                if ($transaksi->harga != $validated['harga']) $historyMessage[] = "Harga '{$transaksi->lok_spk}' diubah dari " . number_format($transaksi->harga) . " menjadi " . number_format($validated['harga']);
                if ($transaksi->pj != $validated['pj']) $historyMessage[] = "PJ '{$transaksi->lok_spk}' diubah dari " . number_format($transaksi->pj) . " menjadi " . number_format($validated['pj']);
                if ($transaksi->invoice != $validated['invoice']) $historyMessage[] = "Invoice '{$transaksi->lok_spk}' diubah dari '{$transaksi->invoice}' menjadi '{$validated['invoice']}'";
                
                $transaksi->update($validated);
                
                if (!empty($historyMessage)) {
                    HistoryEditFakturOnline::create([
                        'faktur_id' => $transaksi->faktur_online_id,
                        'update'    => implode('<br>', $historyMessage),
                        'user_id'   => auth()->id(),
                    ]);
                }

                $totalBaru = TransaksiJualOnline::where('faktur_online_id', $transaksi->faktur_online_id)->sum('harga');
                $transaksi->faktur()->update(['total' => $totalBaru]);
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
                $transaksi = TransaksiJualOnline::with('faktur')->findOrFail($id);
                
                HistoryEditFakturOnline::create([
                    'faktur_id' => $transaksi->faktur_online_id,
                    'update'    => "Menghapus barang (LOK SPK: {$transaksi->lok_spk}) dari faktur.",
                    'user_id'   => auth()->id(),
                ]);

                Barang::where('lok_spk', $transaksi->lok_spk)->update(['status_barang' => 1, 'no_faktur' => null, 'harga_jual' => 0]);
                
                $fakturOnlineId = $transaksi->faktur_online_id;
                $transaksi->delete();

                $totalBaru = TransaksiJualOnline::where('faktur_online_id', $fakturOnlineId)->sum('harga');
                FakturOnline::where('id', $fakturOnlineId)->update(['total' => $totalBaru]);
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
        $currentMonthYear = $tglJual->format('my');

        $lastFaktur = FakturOnline::where('title', 'like', "$kodeFaktur-$currentMonthYear-%")
            ->orderByRaw('LENGTH(title) DESC, title DESC')
            ->first();

        if ($lastFaktur) {
            preg_match('/-(\d+)$/', $lastFaktur->title, $matches);
            $lastNumber = isset($matches[1]) ? (int) $matches[1] : 0;
            $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '001';
        }

        $suggestedNoFak = "$kodeFaktur-$currentMonthYear-$newNumber";

        return response()->json(['suggested_no_fak' => $suggestedNoFak]);
    }
}

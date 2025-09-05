<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\Gudang;
use App\Models\FakturBawah;
use App\Models\Faktur;
use App\Models\FakturOnline;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Kirim;
use Illuminate\Support\Facades\Auth;


class StokGudangController extends Controller
{
    public function index(Request $request)
    {
        // Mendapatkan list Gudang
        $gudangs = Gudang::whereIn('id', range(0, 5))->get();
        $gudangId = optional(Auth::user())->gudang_id;

        // Mendapatkan gudang_id dari request, jika tidak ada maka ambil gudang pertama
        if (in_array($gudangId, [1, 2, 3, 5])) {
            $selectedGudangId = $request->input('gudang_id', $gudangId);
        } else {
            $selectedGudangId = $request->input('gudang_id', $gudangs->first()->id);
        }        

        // Mendapatkan nama gudang berdasarkan gudang_id
        $namaGudang = Gudang::find($selectedGudangId)->nama_gudang;

        // Upload Barang (masuk)
        $uploadBarang = Barang::selectRaw('DATE(t_barang.created_at) as tanggal, COUNT(*) as jumlahMasuk, "Barang masuk (Upload Barang)" as keterangan')
            ->join('users', 't_barang.user_id', '=', 'users.id')
            ->where('users.gudang_id', $selectedGudangId)
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->get();

        // Transaksi Bawah
        $transaksiBawah = FakturBawah::withCount(['barangs as total_barang'])->get();

        $transaksiBawahData = [];
        foreach ($transaksiBawah as $transaksi) {
            // TODO: jika nanti ada mapping prefix nomor_faktur -> gudang_id, isi di sini
            $gudangId = 1;

            if ($gudangId != $selectedGudangId) {
                continue;
            }

            // Tambahan grade (opsional)
            $gradeSuffix = !empty($transaksi->grade) ? " (Grade {$transaksi->grade})" : "";

            $transaksiBawahData[] = (object) [
                'tanggal' => DATE($transaksi->tgl_jual),
                'jumlahKeluar' => $transaksi->total_barang,
                'keterangan' => "<a href=\"" . route('transaksi-faktur-bawah.show', $transaksi->nomor_faktur) . "\" class=\"btn btn-info btn-sm\" target=\"_blank\">View</a>  Transaksi Bawah ke {$transaksi->pembeli}{$gradeSuffix}",
            ];
        }
        
        // Transaksi Offline
        $transaksiOffline = Faktur::withCount(['barangs as total_barang'])->get();

        $transaksiOfflineData = [];
        foreach ($transaksiOffline as $transaksi) {
            $gudangId = match (substr($transaksi->nomor_faktur, 0, 3)) {
                'TKP' => 3,
                default => match (substr($transaksi->nomor_faktur, 0, 2)) {
                    'AT' => 2,
                    'LN' => 2,
                    'VR' => 5,
                    default => null,
                },
            };
            
            if ($gudangId != $selectedGudangId) {
                continue;
            }

            // Tambahan grade (opsional)
            $gradeSuffix = !empty($transaksi->grade) ? " (Grade {$transaksi->grade})" : "";

            $transaksiOfflineData[] = (object) [
                'tanggal' => DATE($transaksi->tgl_jual),
                'jumlahKeluar' => $transaksi->total_barang,
                'keterangan' => "<a href=\"" . route('transaksi-faktur.show', $transaksi->nomor_faktur) . "\" class=\"btn btn-info btn-sm\" target=\"_blank\">View</a>  Transaksi Offline ke {$transaksi->pembeli}{$gradeSuffix}",
            ];
        }

        // Transaksi Online
        $transaksiOnline = FakturOnline::withCount(['barangs as total_barang'])->get();

        $transaksiOnlineData = [];
        foreach ($transaksiOnline as $transaksi) {
            $gudangId = match (substr($transaksi->title, 0, 3)) {
                'PPY', 'NAR' => 3,
                'POD' => 5,
                default => match (substr($transaksi->title, 0, 2)) {
                    'JJ' => 3,
                    'LN' => 5,
                    default => null,
                },
            };

            if ($gudangId != $selectedGudangId) {
                continue;
            }

            // Tambahan grade (opsional)
            $gradeSuffix = !empty($transaksi->grade) ? " (Grade {$transaksi->grade})" : "";

            $toko = $transaksi->toko ?? 'Unknown';

            $transaksiOnlineData[] = (object) [
                'tanggal' => $transaksi->tgl_jual,
                'jumlahKeluar' => $transaksi->total_barang,
                'keterangan' => "<a href=\"" . route('transaksi-faktur-online.show', $transaksi->id) . "\" class=\"btn btn-info btn-sm\" target=\"_blank\">View</a>  Transaksi Online melalui Toko {$toko}{$gradeSuffix}",
            ];
        }

        // Kirim Barang (keluar)
        $kirimBarang = Kirim::withCount(['barangs as total_barang'])
            ->where('pengirim_gudang_id', $selectedGudangId)
            ->where('status', 1)
            ->get();

        $kirimBarangData = [];
        foreach ($kirimBarang as $kirim) {
            $kirimBarangData[] = (object) [
                'tanggal' => Carbon::parse($kirim->dt_terima)->format('Y-m-d'),
                'jumlahKeluar' => $kirim->total_barang,
                'keterangan' => "<a href=\"" . route('kirim-barang.show', $kirim->id) . "\" class=\"btn btn-warning btn-sm\" target=\"_blank\">View</a>  Kirim Barang ke Gudang " . $kirim->penerimaGudang->nama_gudang,
            ];
        }

        // Terima Barang (masuk)
        $terimaBarang = Kirim::withCount(['barangs as total_barang'])
            ->where('penerima_gudang_id', $selectedGudangId)
            ->where('status', 1)
            ->get();

        $terimaBarangData = [];
        foreach ($terimaBarang as $terima) {
            $terimaBarangData[] = (object) [
                'tanggal' => Carbon::parse($terima->dt_terima)->format('Y-m-d'),
                'jumlahMasuk' => $terima->total_barang,
                'keterangan' => "<a href=\"" . route('kirim-barang.show', $terima->id) . "\" class=\"btn btn-warning btn-sm\" target=\"_blank\">View</a>  Terima Barang dari Gudang " . $terima->pengirimGudang->nama_gudang,
            ];
        }

        // Gabungkan semua data & urutkan
        $allData = collect($uploadBarang)
            ->merge($transaksiBawahData)
            ->merge($transaksiOfflineData)
            ->merge($transaksiOnlineData)
            ->merge($kirimBarangData)
            ->merge($terimaBarangData);

        $sortedData = $allData->sortBy('tanggal');

        // Hitung total berjalan
        $totalKeluar = 0;
        $totalMasuk = 0;
        $totalStokSaatIni = 0;
        $datas = [];

        foreach ($sortedData as $data) {
            if (isset($data->jumlahMasuk)) {
                $data->totalSaatIni = $totalStokSaatIni + $data->jumlahMasuk;
                $totalStokSaatIni = $data->totalSaatIni;
                $totalMasuk += $data->jumlahMasuk;
            } else {
                $data->totalSaatIni = $totalStokSaatIni - $data->jumlahKeluar;
                $totalStokSaatIni = $data->totalSaatIni;
                $totalKeluar += $data->jumlahKeluar;
            }
            $datas[] = $data;
        }

        \Log::info("Total Keluar: " . $totalKeluar);
        \Log::info("Total Masuk: " . $totalMasuk);

        // Stok per gudang (current)
        $stokGudangs = Barang::selectRaw('gudang_id, COUNT(*) as total')
            ->whereIn('status_barang', [0, 1, 5])
            ->groupBy('gudang_id')
            ->get()
            ->keyBy('gudang_id');

        $stokGudang = $stokGudangs->get($selectedGudangId, 0);

        return view('pages.stok-gudang.index', compact(
            'datas', 'gudangs', 'namaGudang', 'selectedGudangId', 'totalStokSaatIni', 'stokGudang'
        ));
    }


    public function request_masuk()
    {
        // Mendapatkan auth id pengguna yang sedang login
        $authId = Auth::id();

        // Mengambil data dari model Kirim dengan filter dan relasi
        $requests = Kirim::with([
            'pengirimGudang:id,nama_gudang',
            'penerimaGudang:id,nama_gudang',
            'pengirimUser:id,name',
            'penerimaUser:id,name',
            'barang:lok_spk,tipe'
        ])
        ->where('penerima_user_id', $authId)
        ->where('status', 0)
        ->get();


        return view('pages.stok-gudang.request_barang_masuk', compact('requests'));
    }

    public function history_kirim()
    {

        // Mengambil data dari model Kirim dengan filter dan relasi
        $requests = Kirim::with([
            'pengirimGudang:id,nama_gudang',
            'penerimaGudang:id,nama_gudang',
            'pengirimUser:id,name',
            'penerimaUser:id,name',
            'barang:lok_spk,tipe'
        ])
        ->orderBy('updated_at', 'desc')
        ->get();


        return view('pages.stok-gudang.history_kirim', compact('requests'));
    }

    public function choice_gudang()
    {
        $roleUser = optional(Auth::user())->role;
        
        // Jika bukan admin, redirect langsung ke stok_opname dengan gudang_id user
        if ($roleUser !== 'admin') {
            $userGudangId = optional(Auth::user())->gudang_id;
            return redirect()->route('stokOpname', ['gudang_id' => $userGudangId]);
        }

        $allgudangs = Gudang::all();

        // Mengirim data gudangs dan barangs ke view
        return view('pages.stok-gudang.choice_gudang', compact('allgudangs'));
    }

    public function stok_opname(Request $request)
    {
        $gudangId = $request->query('gudang_id', 'all');
        $jenis = $request->query('jenis'); // Ambil query filter jenis

        $allgudangs = Gudang::all();

        if ($gudangId === 'all') {
            $selectedGudang = (object) ['nama_gudang' => 'Semua Gudang', 'id' => 'all'];
            $query = Barang::with('gudang')->whereIn('status_barang', [0, 1, 5]);
        } else {
            $selectedGudang = Gudang::findOrFail($gudangId);
            $query = Barang::with('gudang')
                ->where('gudang_id', $gudangId)
                ->whereIn('status_barang', [0, 1, 5]);
        }

        if ($jenis) {
            $query->whereRaw('LOWER(jenis) = ?', [strtolower($jenis)]);
        }

        $barangs = $query->get();

        return view('pages.stok-gudang.stok_opname', compact('selectedGudang', 'barangs', 'allgudangs', 'jenis'));
    }
    

    public function handleRequest(Request $request)
    {
        // Ambil data lok_spk, pengirim_gudang_id, dan penerima_gudang_id yang diceklis
        $ids = $request->input('id');
        $lok_spks = $request->input('lok_spk');
        $pengirimGudangIds = $request->input('pengirim_gudang_id');
        $penerimaGudangIds = $request->input('penerima_gudang_id');

        // Pastikan tombol "Terima" atau "Tolak" diklik
        if ($request->input('action') == 'terima') {
            // Logika untuk menerima permintaan
            foreach ($ids as $index => $id) {
                $lok_spk = $lok_spks[$index];
                $pengirimGudangId = $pengirimGudangIds[$index];
                $penerimaGudangId = $penerimaGudangIds[$index];

                // Update data di model Kirim
                Kirim::where('id', $id)->update([
                    'status' => 1,
                    'dt_terima' => Carbon::now(),
                ]);

                // Update data di model Barang
                Barang::where('lok_spk', $lok_spk)->update([
                    'gudang_id' => $penerimaGudangId,
                    'status_barang' => 1,
                ]);
            }
            return redirect()->back()->with('success', 'Permintaan diterima.');
        } elseif ($request->input('action') == 'tolak') {
            // Logika untuk menolak permintaan
            foreach ($ids as $index => $id) {
                $lok_spk = $lok_spks[$index];
                $pengirimGudangId = $pengirimGudangIds[$index];
                $penerimaGudangId = $penerimaGudangIds[$index];

                // Update data di model Kirim
                Kirim::where('id', $id)->update([
                    'status' => 2,
                    'dt_terima' => Carbon::now(),
                ]);

                // Update data di model Barang
                Barang::where('lok_spk', $lok_spk)->update([
                    'gudang_id' => $pengirimGudangId,
                    'status_barang' => 1,
                ]);
            }
            return redirect()->back()->with('success', 'Permintaan ditolak.');
        }

        return redirect()->back()->with('error', 'Tidak ada aksi yang dilakukan.');
    }

    public function kirimBarang(Request $request)
    {
        // Ambil data lok_spk, pengirim_gudang_id, dan penerima_gudang_id yang diceklis
        $lok_spks = $request->input('lok_spk');
        $gudangPenerimaId = $request->input('gudang_id');
        $gudangPenerima = Gudang::find($gudangPenerimaId);
        $pj_gudang = $gudangPenerima->pj_gudang;
        // Mendapatkan auth id pengguna yang sedang login
        $authId = Auth::id();
        $gudang = Gudang::where('pj_gudang', $authId)->select('id', 'nama_gudang')->first();
        $gudangIds = $gudang->id; 

        // Pastikan tombol "Terima" atau "Tolak" diklik
        if ($request->input('action') == 'kirim') {
            // Logika untuk menerima permintaan
            foreach ($lok_spks as $index => $lok_spk) {

                Kirim::create([
                    'lok_spk' => $lok_spk,
                    'pengirim_gudang_id' => $gudangIds,
                    'penerima_gudang_id' => $gudangPenerimaId,
                    'pengirim_user_id' => Auth::id(),
                    'penerima_user_id' => $pj_gudang,
                    'status' => 0,
                    'dt_kirim' => Carbon::now(),
                ]);

                // Update data di model Barang
                Barang::where('lok_spk', $lok_spk)->update([
                    'status_barang' => 0,
                ]);
            }
            return redirect()->back()->with('success', 'Permintaan diterima.');
        } 

        return redirect()->back()->with('error', 'Tidak ada aksi yang dilakukan.');
    }


}

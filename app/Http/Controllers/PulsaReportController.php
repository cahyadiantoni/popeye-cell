<?php

namespace App\Http\Controllers;

use App\Models\PulsaReport;
use App\Models\PulsaMaster;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;
use App\Exports\PulsaReportExport;
use Maatwebsite\Excel\Facades\Excel;

class PulsaReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $storeOptions = PulsaMaster::select('kode', 'nama_toko')
                                    ->distinct()
                                    ->orderBy('nama_toko', 'asc')
                                    ->get();

        if ($request->ajax()) {
            $query = PulsaReport::query();

            if ($request->filled('tanggal_mulai')) {
                try {
                    $query->where('Tanggal', '>=', Carbon::parse($request->tanggal_mulai)->format('Y-m-d'));
                } catch (\Exception $e) {
                    // Abaikan
                }
            }
            if ($request->filled('tanggal_selesai')) {
                try {
                    $query->where('Tanggal', '<=', Carbon::parse($request->tanggal_selesai)->format('Y-m-d'));
                } catch (\Exception $e) {
                    // Abaikan
                }
            }

            $reports = $query->orderBy('Tanggal', 'desc')->orderBy('id', 'desc')->get();

            $searchableMasterFields = [
                'pasca_bayar1', 'pasca_bayar2', 'token1', 'token2',
                'pam1', 'pam2', 'pulsa1', 'pulsa2', 'pulsa3',
            ];
            $pulsaMasters = PulsaMaster::where(function ($q_master) use ($searchableMasterFields) {
                foreach ($searchableMasterFields as $field) {
                    $q_master->orWhere(function($sub_q) use ($field){
                        $sub_q->whereNotNull($field)->where($field, '!=', '');
                    });
                }
            })->select(['kode', 'nama_toko', ...$searchableMasterFields])->get();

            $processedReports = [];
            foreach ($reports as $report) {
                $kodeMasterMatch = null;
                $namaTokoMatch = null;
                $matchFound = false;

                foreach ($pulsaMasters as $master) {
                    foreach ($searchableMasterFields as $field) {
                        $masterValue = $master->{$field};
                        if (!empty($masterValue) && str_contains((string)$report->Keterangan, (string)$masterValue)) {
                            $kodeMasterMatch = $master->kode;
                            $namaTokoMatch = $master->nama_toko;
                            $matchFound = true;
                            break; 
                        }
                    }
                    if ($matchFound) {
                        break; 
                    }
                }
                
                $report->kode_master_match = $kodeMasterMatch; 
                $report->nama_toko_master_match = $namaTokoMatch;
                $processedReports[] = $report;
            }
            
            $reportCollection = collect($processedReports);

            if ($request->filled('filter_kode_toko')) {
                $filterKode = $request->filter_kode_toko;
                $reportCollection = $reportCollection->filter(function ($item) use ($filterKode) {
                    return $item->kode_master_match === $filterKode;
                });
            }

            if ($request->filled('filter_cek')) {
                $cekStatus = $request->filter_cek;
                if ($cekStatus === 'ada_kode') {
                    $reportCollection = $reportCollection->filter(function ($item) {
                        return !is_null($item->kode_master_match);
                    });
                } elseif ($cekStatus === 'tidak_ada_kode') {
                    $reportCollection = $reportCollection->filter(function ($item) {
                        return is_null($item->kode_master_match);
                    });
                }
            }
            
            return DataTables::of($reportCollection)
                ->addColumn('kode_master', function ($row) {
                    return $row->kode_master_match ?? '-';
                })
                ->addColumn('nama_toko_master', function ($row) {
                    return $row->nama_toko_master_match ?? '-';
                })
                ->addColumn('tipe_transaksi', function ($row) { // KOLOM BARU "Transaksi"
                    // Pastikan $row->Cabang adalah string untuk `match` atau `switch`
                    $cabangValue = (string) $row->Cabang;
                    return match ($cabangValue) {
                        '0000' => 'PAM',
                        '0001' => 'PASCABAYAR',
                        '0253' => 'TOKEN',
                        '0998' => 'PULSA',
                        default => $cabangValue, // Atau '-' jika tidak ada yang cocok dan tidak ingin menampilkan kode cabang asli
                    };
                })
                ->editColumn('Tanggal', function ($row) {
                    // Akses $row->Tanggal karena $row adalah item dari $reportCollection (objek PulsaReport yang dimodifikasi)
                    return Carbon::parse($row->Tanggal)->translatedFormat('d M Y');
                })
                ->editColumn('Jumlah', function ($row) {
                    return 'Rp. ' . number_format($row->Jumlah, 2, ',', '.');
                })
                ->editColumn('Saldo', function ($row) {
                    return 'Rp. ' . number_format($row->Saldo, 2, ',', '.');
                })
                 // rawColumns perlu menyertakan 'tipe_transaksi' jika mengandung HTML,
                 // tapi karena hanya teks, tidak wajib.
                ->rawColumns(['kode_master', 'nama_toko_master'])
                ->make(true);
        }

        return view('pages.pulsa-report.index', compact('storeOptions'));
    }

    /**
     * Store a newly created resource in storage from a CSV file.
     */
    public function store(Request $request)
    {
        $request->validate([
            'filecsv' => 'required|mimes:csv,txt|max:5120',
        ]);

        DB::beginTransaction();
        $insertedCount = 0;
        $skippedFormatCount = 0;
        $skippedDuplicatesCount = 0;
        $currentYear = Carbon::now()->year;

        try {
            $path = $request->file('filecsv')->getRealPath();
            
            $reader = IOFactory::createReader('Csv');
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($path);
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestDataRow();

            for ($rowIndex = 6; $rowIndex <= $highestRow; $rowIndex++) {
                $colA_Tanggal       = $sheet->getCell('A' . $rowIndex)->getValue();
                $colB_Keterangan    = $sheet->getCell('B' . $rowIndex)->getValue();
                $colC_Cabang        = $sheet->getCell('C' . $rowIndex)->getValue();
                $colD_Jumlah        = $sheet->getCell('D' . $rowIndex)->getValue();
                $colE_Jenis         = $sheet->getCell('E' . $rowIndex)->getValue();
                $colF_Saldo         = $sheet->getCell('F' . $rowIndex)->getValue();

                $rawTanggal = is_string($colA_Tanggal) ? ltrim(trim($colA_Tanggal), "'") : $colA_Tanggal;
                
                if (!preg_match('/^\d{1,2}\/\d{1,2}$/', (string)$rawTanggal)) {
                    $skippedFormatCount++;
                    continue; 
                }

                try {
                    $tanggalDb = Carbon::createFromFormat('d/m', $rawTanggal)->year($currentYear)->format('Y-m-d');
                } catch (Exception $e) {
                    Log::warning("Baris CSV $rowIndex: Format tanggal tidak valid ('$rawTanggal'). Baris dilewati.");
                    $skippedFormatCount++;
                    continue;
                }
                
                $keteranganDb = trim((string)$colB_Keterangan);
                
                if (empty($keteranganDb)) {
                    Log::warning("Baris CSV $rowIndex: Keterangan kosong. Baris dilewati.");
                    $skippedFormatCount++;
                    continue;
                }

                $cabangDb = is_string($colC_Cabang) ? ltrim(trim($colC_Cabang), "'") : trim((string)$colC_Cabang);
                
                $jumlahClean = preg_replace('/[^\d.-]/', '', (string)$colD_Jumlah);
                $saldoClean  = preg_replace('/[^\d.-]/', '', (string)$colF_Saldo);

                $jumlahDb = is_numeric($jumlahClean) ? floatval($jumlahClean) : 0;
                $saldoDb  = is_numeric($saldoClean) ? floatval($saldoClean) : 0; // Pastikan $saldoDb sudah bersih

                $jenisDb = trim((string)$colE_Jenis);

                // --- PENGECEKAN DUPLIKAT BERDASARKAN Tanggal, Keterangan, DAN Saldo ---
                $exists = PulsaReport::where('Tanggal', $tanggalDb)
                                     ->where('Keterangan', $keteranganDb)
                                     ->where('Saldo', $saldoDb) // Tambahkan pengecekan Saldo
                                     // Pertimbangkan menambahkan field lain jika perlu:
                                     // ->where('Jumlah', $jumlahDb)
                                     // ->where('Jenis', $jenisDb)
                                     // ->where('Cabang', $cabangDb)
                                     ->exists();

                if ($exists) {
                    Log::info("Baris CSV $rowIndex: Data duplikat ditemukan berdasarkan Tanggal '$tanggalDb', Keterangan '$keteranganDb', dan Saldo '$saldoDb'. Baris dilewati.");
                    $skippedDuplicatesCount++;
                    continue; 
                }
                // --- AKHIR PENGECEKAN DUPLIKAT ---

                PulsaReport::create([
                    'Tanggal'       => $tanggalDb,
                    'Keterangan'    => $keteranganDb,
                    'Cabang'        => $cabangDb,
                    'Jumlah'        => $jumlahDb,
                    'Jenis'         => $jenisDb,
                    'Saldo'         => $saldoDb,
                ]);
                $insertedCount++;
            }

            DB::commit();
            return redirect()->back()->with('success', "Impor CSV berhasil. Data baru: $insertedCount, duplikat dilewati: $skippedDuplicatesCount, format salah dilewati: $skippedFormatCount.");

        } catch (\PhpOffice\PhpSpreadsheet\Exception $e) {
            DB::rollBack();
            Log::error('Kesalahan Spreadsheet (CSV): ' . $e->getMessage() . ' Stack: ' . $e->getTraceAsString());
            return redirect()->back()->with('error', 'Kesalahan saat membaca file CSV: ' . $e->getMessage());
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            Log::error('Kesalahan Database (CSV): ' . $e->getMessage() . ' Stack: ' . $e->getTraceAsString());
            return redirect()->back()->with('error', 'Kesalahan database saat menyimpan data dari CSV.');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Kesalahan Umum (CSV): ' . $e->getMessage() . ' Stack: ' . $e->getTraceAsString());
            return redirect()->back()->with('error', 'Impor CSV gagal: ' . $e->getMessage() . (isset($rowIndex) ? ' (Baris CSV terakhir diproses: ' . $rowIndex . ')' : ''));
        }
    }

    public function exportExcel(Request $request)
    {
        // Logika pengambilan data dan filter SAMA PERSIS dengan yang ada di metode index() saat $request->ajax()
        // Ini untuk memastikan data yang diekspor konsisten dengan yang ditampilkan
        $query = PulsaReport::query();

        if ($request->filled('tanggal_mulai')) {
            try { $query->where('Tanggal', '>=', Carbon::parse($request->tanggal_mulai)->format('Y-m-d')); } 
            catch (\Exception $e) { /* Abaikan */ }
        }
        if ($request->filled('tanggal_selesai')) {
            try { $query->where('Tanggal', '<=', Carbon::parse($request->tanggal_selesai)->format('Y-m-d')); } 
            catch (\Exception $e) { /* Abaikan */ }
        }

        $reports = $query->orderBy('Tanggal', 'desc')->orderBy('id', 'desc')->get();

        $searchableMasterFields = [
            'pasca_bayar1', 'pasca_bayar2', 'token1', 'token2',
            'pam1', 'pam2', 'pulsa1', 'pulsa2', 'pulsa3',
        ];
        $pulsaMasters = PulsaMaster::where(function ($q_master) use ($searchableMasterFields) {
            foreach ($searchableMasterFields as $field) {
                $q_master->orWhere(function($sub_q) use ($field){
                    $sub_q->whereNotNull($field)->where($field, '!=', '');
                });
            }
        })->select(['kode', 'nama_toko', ...$searchableMasterFields])->get();

        $processedReports = [];
        foreach ($reports as $report) {
            $kodeMasterMatch = null;
            $namaTokoMatch = null;
            $matchFound = false;
            foreach ($pulsaMasters as $master) {
                foreach ($searchableMasterFields as $field) {
                    $masterValue = $master->{$field};
                    if (!empty($masterValue) && str_contains((string)$report->Keterangan, (string)$masterValue)) {
                        $kodeMasterMatch = $master->kode;
                        $namaTokoMatch = $master->nama_toko;
                        $matchFound = true;
                        break; 
                    }
                }
                if ($matchFound) break; 
            }
            $report->kode_master_match = $kodeMasterMatch; 
            $report->nama_toko_master_match = $namaTokoMatch;
            // Hitung tipe_transaksi di sini juga agar konsisten
            $cabangValue = (string) $report->Cabang;
            $report->tipe_transaksi = match ($cabangValue) {
                '0000' => 'PAM',
                '0001' => 'PASCABAYAR',
                '0253' => 'TOKEN',
                '0998' => 'PULSA',
                default => $cabangValue,
            };
            $processedReports[] = $report;
        }
        
        $reportCollection = collect($processedReports);

        if ($request->filled('filter_kode_toko')) {
            $filterKode = $request->filter_kode_toko;
            $reportCollection = $reportCollection->filter(fn ($item) => $item->kode_master_match === $filterKode);
        }

        if ($request->filled('filter_cek')) {
            $cekStatus = $request->filter_cek;
            if ($cekStatus === 'ada_kode') {
                $reportCollection = $reportCollection->filter(fn ($item) => !is_null($item->kode_master_match));
            } elseif ($cekStatus === 'tidak_ada_kode') {
                $reportCollection = $reportCollection->filter(fn ($item) => is_null($item->kode_master_match));
            }
        }
        // Akhir logika pengambilan data yang disalin dari index()

        $timestamp = Carbon::now()->format('Ymd_His');
        return Excel::download(new PulsaReportExport($reportCollection), "Laporan_Pulsa_{$timestamp}.xlsx");
    }
}
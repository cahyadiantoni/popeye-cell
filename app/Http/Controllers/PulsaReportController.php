<?php

namespace App\Http\Controllers;

use App\Models\PulsaReport; // Model yang sudah kita buat
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception; // Tambahkan ini

class PulsaReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = PulsaReport::orderBy('Tanggal', 'desc')->latest()->get(); // Urutkan berdasarkan tanggal terbaru
            return DataTables::of($data)
                ->editColumn('Tanggal', function ($row) {
                    return Carbon::parse($row->Tanggal)->translatedFormat('d M Y');
                })
                ->editColumn('Jumlah', function ($row) {
                    return 'Rp. ' . number_format($row->Jumlah, 2, ',', '.');
                })
                ->editColumn('Saldo', function ($row) {
                    return 'Rp. ' . number_format($row->Saldo, 2, ',', '.');
                })
                // Tambahkan kolom aksi jika perlu
                ->make(true);
        }
        return view('pages.pulsa-report.index');
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
}
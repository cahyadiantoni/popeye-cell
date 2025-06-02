<?php

namespace App\Http\Controllers;

use App\Models\TokpedInputOrder;
use App\Models\TokpedDataOrder;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TokpedOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = TokpedDataOrder::with('tokpedInputOrder')->latest()->get();

            return DataTables::of($data)
                ->addColumn('nama_toko', function ($row) {
                    return $row->tokpedInputOrder->nama_toko ?? '-';
                })
                ->editColumn('payment_at', function ($row) {
                    return optional($row->payment_at)->format('Y-m-d H:i');
                })
                ->editColumn('completed_at', function ($row) {
                    return optional($row->completed_at)->format('Y-m-d H:i');
                })
                ->editColumn('cancelled_at', function ($row) {
                    return optional($row->cancelled_at)->format('Y-m-d H:i');
                })
                ->make(true);
        }

        return view('pages.tokped-order.index');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'filedata' => 'required|mimes:xlsx,xls,csv|max:5120', // max 5MB
        ]);

        DB::beginTransaction();

        try {
            $path = $request->file('filedata')->getRealPath();
            $spreadsheet = IOFactory::load($path);
            $sheet = $spreadsheet->getActiveSheet();

            // 1. Ambil data untuk TokpedInputOrder dari sel B1, B2, B3
            $nama_toko = $sheet->getCell('B1')->getValue();
            $periode_laporan = $sheet->getCell('B2')->getValue();
            $cellTanggalPenarikan = $sheet->getCell('B3')->getValue();

            $tanggal_penarikan_data = null;
            if (!empty($cellTanggalPenarikan)) {
                if (is_numeric($cellTanggalPenarikan)) {
                    $tanggal_penarikan_data = Date::excelToDateTimeObject($cellTanggalPenarikan)->format('Y-m-d H:i:s');
                } else {
                    try {
                        $tanggal_penarikan_data = Carbon::parse($cellTanggalPenarikan)->format('Y-m-d H:i:s');
                    } catch (\Exception $e) {
                        // Coba format lain jika ada atau lempar error jika format tidak sesuai
                        throw new \Exception("Format tanggal penarikan data (B3) tidak valid: " . $cellTanggalPenarikan);
                    }
                }
            } else {
                throw new \Exception("Tanggal penarikan data (B3) tidak boleh kosong.");
            }


            // Simpan ke TokpedInputOrder
            $tokpedInputOrder = TokpedInputOrder::create([
                'nama_toko' => $nama_toko,
                'periode_laporan' => $periode_laporan,
                'tanggal_penarikan_data' => $tanggal_penarikan_data,
            ]);



            $rows = $sheet->toArray(null, true, true, true); // Ambil sebagai array asosiatif (A, B, C => 0, 1, 2)
            $insertedCount = 0;
            $skippedCount = 0;

            $dataRows = array_slice($rows, 5); 
            $highestRow = $sheet->getHighestDataRow();

            for ($rowIndex = 6; $rowIndex <= $highestRow; $rowIndex++) {
                $rowData = $sheet->rangeToArray('B' . $rowIndex . ':' . $sheet->getHighestDataColumn() . $rowIndex, NULL, TRUE, FALSE)[0];
                // $rowData sekarang adalah array numerik dimulai dari kolom B

                // Pastikan baris tidak kosong (cek kolom B - Nomor Invoice)
                $invoice_number = $rowData[0] ?? null; // B -> index 0 di $rowData
                if (empty($invoice_number)) {
                    continue; // Lewati baris jika nomor invoice kosong
                }

                $rawPaymentAt = $rowData[1] ?? null; // C -> index 1
                $latest_status = $rowData[2] ?? null; // D -> index 2
                $rawCompletedDate = $rowData[3] ?? null; // E -> index 3
                $rawCompletedTime = $rowData[4] ?? null; // F -> index 4
                $rawCancelledDate = $rowData[5] ?? null; // G -> index 5
                $rawCancelledTime = $rowData[6] ?? null; // H -> index 6
                $product_name = $rowData[7] ?? null; // I -> index 7

                // Parsing Tanggal Pembayaran (dd-mm-yyyy HH:MM:SS)
                $payment_at = null;
                if (!empty($rawPaymentAt)) {
                    if (is_numeric($rawPaymentAt)) {
                        $payment_at = Date::excelToDateTimeObject($rawPaymentAt)->format('Y-m-d H:i:s');
                    } else {
                        try {
                            $payment_at = Carbon::createFromFormat('d-m-Y H:i:s', (string)$rawPaymentAt)->format('Y-m-d H:i:s');
                        } catch (\Exception $e) {
                            // Jika error, coba parse dengan format umum
                            try {
                                $payment_at = Carbon::parse((string)$rawPaymentAt)->format('Y-m-d H:i:s');
                            } catch (\Exception $ex) {
                                // Log error atau skip baris ini
                                // throw new \Exception("Format Tanggal Pembayaran (Kolom C, Baris $rowIndex) tidak valid: $rawPaymentAt");
                                $skippedCount++;
                                continue;
                            }
                        }
                    }
                }

                // Fungsi helper untuk parse tanggal dan waktu gabungan
                $parseCombinedDateTime = function ($rawDate, $rawTime, $colDate, $colTime, $rowIndex) {
                    if (empty($rawDate)) {
                        return null;
                    }
                    $datePart = '';
                    $timePart = '00:00:00';

                    if (is_numeric($rawDate)) {
                        $datePart = Date::excelToDateTimeObject($rawDate)->format('Y-m-d');
                    } else {
                        try {
                            $datePart = Carbon::createFromFormat('d-m-Y', (string)$rawDate)->format('Y-m-d');
                        } catch (\Exception $e) {
                             throw new \Exception("Format Tanggal (Kolom $colDate, Baris $rowIndex) tidak valid: $rawDate");
                        }
                    }

                    if (!empty($rawTime)) {
                        if (is_numeric($rawTime)) { // Excel time is float 0-1
                            $timePart = Date::excelToDateTimeObject($rawTime)->format('H:i:s');
                        } else {
                             try {
                                // Coba parse waktu jika berupa string 'H:i:s' atau 'H:i'
                                $timePart = Carbon::parse((string)$rawTime)->format('H:i:s');
                            } catch (\Exception $e) {
                                throw new \Exception("Format Waktu (Kolom $colTime, Baris $rowIndex) tidak valid: $rawTime");
                            }
                        }
                    }
                    return $datePart . ' ' . $timePart;
                };

                $completed_at = null;
                if(!empty($rawCompletedDate)){
                    $completed_at = $parseCombinedDateTime($rawCompletedDate, $rawCompletedTime, 'E', 'F', $rowIndex);
                }

                $cancelled_at = null;
                if(!empty($rawCancelledDate)){
                     $cancelled_at = $parseCombinedDateTime($rawCancelledDate, $rawCancelledTime, 'G', 'H', $rowIndex);
                }


                // Validasi data penting lainnya
                if (empty($product_name)) {
                    $skippedCount++;
                    continue;
                }

                $exists = TokpedDataOrder::where('invoice_number', $invoice_number)
                                        ->where('product_name', $product_name)
                                        ->where('payment_at', $payment_at)
                                        ->exists();

                if ($exists) {
                    $skippedCount++;
                    continue;
                }

                TokpedDataOrder::create([
                    'tokped_input_order_id' => $tokpedInputOrder->id,
                    'invoice_number' => $invoice_number,
                    'payment_at' => $payment_at,
                    'latest_status' => $latest_status,
                    'completed_at' => $completed_at,
                    'cancelled_at' => $cancelled_at,
                    'product_name' => trim($product_name),
                ]);
                $insertedCount++;
            }

            DB::commit();
            return redirect()->back() // Ganti dengan nama route Anda
                           ->with('success', "Upload berhasil. Data Order masuk: $insertedCount, duplikat/dilewati: $skippedCount.");

        } catch (\PhpOffice\PhpSpreadsheet\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Kesalahan terkait file Excel: ' . $e->getMessage());
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            // Tangkap error spesifik jika ada, misal foreign key constraint
            return redirect()->back()->with('error', 'Kesalahan database: ' . $e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Upload gagal: ' . $e->getMessage() . ' (Baris: ' . (isset($rowIndex) ? $rowIndex : 'N/A') . ')');
        }
    }
}
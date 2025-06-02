<?php

namespace App\Http\Controllers;

use App\Models\ReturnBarang;
use Illuminate\Http\Request;
use App\Models\TokpedInputDeposit;
use App\Models\TokpedDataDeposit;
use App\Models\FakturOnline;
use App\Models\TokpedDataOrder;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use App\Exports\RekapTokpedExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TokpedDepositController extends Controller
{

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = TokpedDataDeposit::latest()->get();

            return DataTables::of($data)
                ->editColumn('nominal', function ($row) {
                    $formatted = 'Rp. ' . number_format($row->nominal, 0, ',', '.');
                    $color = $row->nominal < 0 ? 'red' : 'green';
                    return '<span style="color:' . $color . '">' . $formatted . '</span>';
                })
                ->editColumn('balance', function ($row) {
                    return 'Rp. ' . number_format($row->balance, 0, ',', '.');
                })
                ->rawColumns(['nominal', 'balance']) // agar HTML-nya tidak di-escape
                ->make(true);
        }

        return view('pages.tokped-deposit.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'filedata' => 'required|mimes:xlsx,xls,csv',
        ]);

        DB::beginTransaction(); // 🟡 Mulai transaksi

        try {
            $path = $request->file('filedata')->getRealPath();
            $spreadsheet = IOFactory::load($path);
            $sheet = $spreadsheet->getActiveSheet();

            // Ambil data TokpedInputDeposit dari sel B2-B5
            $cellValue = $sheet->getCell('B2')->getValue();
            $tgl_penarikan = is_numeric($cellValue)
                ? Date::excelToDateTimeObject($cellValue)->format('Y-m-d H:i:s')
                : date('Y-m-d H:i:s', strtotime($cellValue));
            $dana_dalam_pengawasan = $sheet->getCell('B3')->getValue();
            $saldo_akhir = $sheet->getCell('B4')->getValue();
            $periode = $sheet->getCell('B5')->getValue();

            // Simpan ke TokpedInputDeposit
            TokpedInputDeposit::create([
                'tgl_penarikan' => $tgl_penarikan,
                'dana_dalam_pengawasan' => $dana_dalam_pengawasan,
                'saldo_akhir' => $saldo_akhir,
                'periode' => $periode,
            ]);

            $rows = $sheet->toArray();
            $inserted = 0;
            $skipped = 0;

            foreach ($rows as $index => $row) {
                if ($index < 7) continue;

                $dateRaw = $row[0];
                if (empty($dateRaw)) continue;

                if (is_numeric($dateRaw)) {
                    $date = Date::excelToDateTimeObject($dateRaw)->format('Y-m-d H:i:s');
                } else {
                    $date = date('Y-m-d H:i:s', strtotime($dateRaw));
                }

                $mutation = $row[1] ?? null;
                $description = $row[2] ?? null;
                $description = trim(preg_replace('/\s+/', ' ', $description));
                $nominal = isset($row[3]) ? (int) str_replace(',', '', $row[3]) : null;
                $balance = isset($row[4]) ? (int) str_replace(',', '', $row[4]) : null;

                if (!$description || !$nominal || !$balance) {
                    throw new \Exception("Data tidak lengkap di baris $index");
                }

                [$description_short, $invoice_full] = explode(' - ', $description . ' - ');
                $invoice_end = strrchr($invoice_full, '/');
                $invoice_end = $invoice_end ? substr($invoice_end, 1) : null;

                // Cek duplikat
                $exists = TokpedDataDeposit::where([
                    ['date', '=', $date],
                    ['mutation', '=', $mutation],
                    ['description', '=', $description],
                    ['nominal', '=', $nominal],
                ])->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                TokpedDataDeposit::create([
                    'date' => $date,
                    'mutation' => $mutation,
                    'description' => $description,
                    'description_short' => $description_short,
                    'invoice_full' => $invoice_full,
                    'invoice_end' => $invoice_end,
                    'nominal' => $nominal,
                    'balance' => $balance,
                ]);

                $inserted++;
            }

            DB::commit(); // ✅ Simpan semua jika sukses
            return redirect()->back()->with('success', "Upload berhasil. Data masuk: $inserted, duplikat: $skipped");

        } catch (\Exception $e) {
            DB::rollBack(); // ❌ Batalkan semua jika error
            return redirect()->back()->with('error', 'Upload gagal: ' . $e->getMessage());
        }
    }

    public function rekap(Request $request)
    {
        if ($request->ajax()) {
            $data = $this->getRekapData($request);

            return DataTables::of($data)
                ->editColumn('total_nominal_faktur', fn($row) => 'Rp. ' . number_format($row['total_nominal_faktur'], 0, ',', '.'))
                ->editColumn('total_uang_masuk', fn($row) => 'Rp. ' . number_format($row['total_uang_masuk'], 0, ',', '.'))
                ->editColumn('selisih', fn($row) => '<span style="color:' . ($row['selisih'] < 0 ? 'red' : 'black') . '">Rp. ' . number_format($row['selisih'], 0, ',', '.') . '</span>')
                ->rawColumns(['title', 'selisih'])
                ->make(true);
        }

        return view('pages.tokped-deposit.rekap');
    }

    public function export(Request $request)
    {
        
        $data = $this->getRekapData($request);
        return Excel::download(new RekapTokpedExport($data), 'RekapTokped.xlsx');
    }

    private function getRekapData(Request $request)
    {
        $query = FakturOnline::with('transaksiJuals');

        // Filter toko berdasarkan prefix
        if ($request->filled('kode_faktur')) {
            if ($request->kode_faktur == 'Lain') {
                $query->where(function ($q) {
                    $q->where('title', 'not like', 'PPY%')
                        ->where('title', 'not like', 'POD%')
                        ->where('title', 'not like', 'JJ%')
                        ->where('title', 'not like', 'NAR%');
                });
            } else {
                $query->where('title', 'like', $request->kode_faktur . '%');
            }
        }

        // Filter tanggal mulai
        if ($request->filled('tanggal_mulai')) {
            $query->whereDate('tgl_jual', '>=', Carbon::parse($request->tanggal_mulai));
        }

        // Filter tanggal selesai
        if ($request->filled('tanggal_selesai')) {
            $query->whereDate('tgl_jual', '<=', Carbon::parse($request->tanggal_selesai));
        }

        $fakturs = $query->orderBy('tgl_jual', 'asc')->get();

        // Ambil semua invoice number dari TokpedDataOrder yang statusnya dibatalkan (7 digit terakhir)
        $cancelledTokpedOrderInvoices = TokpedDataOrder::where('latest_status', 'like', 'Dibatalkan%')
            ->pluck('invoice_number')
            ->map(function ($invoice) {
                $clean = preg_replace('/\D/', '', $invoice);
                return Str::substr($clean, -7);
            })
            ->filter()
            ->unique()
            ->toArray();

        $data = $fakturs->map(function ($faktur) use ($cancelledTokpedOrderInvoices) {
            $title = $faktur->title;
            $tgl = $faktur->tgl_jual;
            $total_unit_faktur = $faktur->transaksiJuals->count();
            $total_nominal_faktur = $faktur->total;

            $invoicesFaktur = $faktur->transaksiJuals->pluck('invoice')
                ->filter()
                ->map(function ($invoice) {
                    $clean = preg_replace('/\D/', '', $invoice);
                    return Str::substr($clean, -7);
                })
                ->unique()
                ->toArray();
            
            // Cocokkan dengan TokpedDataDeposit (invoice_end)
            $matchedDepositInvoicesRaw = TokpedDataDeposit::where(function ($query) use ($invoicesFaktur) {
                foreach ($invoicesFaktur as $inv) {
                    $query->orWhereRaw("RIGHT(REGEXP_REPLACE(invoice_end, '[^0-9]', ''), 7) = ?", [$inv]);
                }
            })->when(!empty($invoicesFaktur), function ($q) { // Hanya query jika $invoicesFaktur tidak kosong
                return $q;
            }, function ($q) { // Jika $invoicesFaktur kosong, return query yg tidak menghasilkan apa-apa
                return $q->whereRaw('1=0');
            })->pluck('invoice_end')->toArray();

            $matchedDepositInvoicesCleaned = collect($matchedDepositInvoicesRaw)->map(function($invEnd){
                $clean = preg_replace('/\D/', '', $invEnd);
                return Str::substr($clean, -7);
            })->unique()->toArray();

            // Hitung total_unit_invoice berdasarkan transaksiJuals yang invoicenya cocok dengan deposit
            $total_unit_invoice = $faktur->transaksiJuals->filter(function($item) use ($matchedDepositInvoicesCleaned) {
                if (empty($item->invoice)) return false;
                $cleanFakturInvoice = preg_replace('/\D/', '', $item->invoice);
                $last7FakturInvoice = Str::substr($cleanFakturInvoice, -7);
                return in_array($last7FakturInvoice, $matchedDepositInvoicesCleaned);
            })->count();

            $total_uang_masuk = TokpedDataDeposit::where(function ($query) use ($invoicesFaktur) {
                foreach ($invoicesFaktur as $inv) {
                    $query->orWhereRaw("RIGHT(REGEXP_REPLACE(invoice_end, '[^0-9]', ''), 7) = ?", [$inv]);
                }
            })->when(!empty($invoicesFaktur), function ($q) {
                return $q;
            }, function ($q) {
                return $q->whereRaw('1=0');
            })->sum('nominal');

            // Hitung total_unit_dibatalkan
            $total_unit_dibatalkan = 0;
            if (!empty($cancelledTokpedOrderInvoices)) {
                $total_unit_dibatalkan = $faktur->transaksiJuals->filter(function ($item) use ($cancelledTokpedOrderInvoices) {
                    if (empty($item->invoice)) return false;
                    $cleanInvoice = preg_replace('/\D/', '', $item->invoice);
                    $last7Digits = Str::substr($cleanInvoice, -7);
                    return in_array($last7Digits, $cancelledTokpedOrderInvoices);
                })->count();
            }
            
            $selisih = $total_nominal_faktur - $total_uang_masuk;
            
            // Logika keterangan baru
            $keterangan = $total_unit_faktur === ($total_unit_invoice + $total_unit_dibatalkan) ? 'Lunas' : 'Belum Lunas';

            // ... (sisa map, bonusan, return tetap sama) ...
            $bonusPerBulan = $faktur->transaksiJuals
                ->filter(function($item) use ($matchedDepositInvoicesCleaned) { // Gunakan $matchedDepositInvoicesCleaned
                    if (empty($item->invoice)) return false;
                    $cleanFakturInvoice = preg_replace('/\D/', '', $item->invoice);
                    $last7FakturInvoice = Str::substr($cleanFakturInvoice, -7);
                    return in_array($last7FakturInvoice, $matchedDepositInvoicesCleaned);
                })
                ->map(function ($item) {
                     // Ambil invoice_end yang bersih untuk mencocokkan dengan TokpedDataDeposit
                    $cleanFakturInvoice = preg_replace('/\D/', '', $item->invoice);
                    $last7FakturInvoice = Str::substr($cleanFakturInvoice, -7);

                    // Cari deposit yang invoice_end (7 digit terakhir bersih) nya cocok
                    $deposit = TokpedDataDeposit::whereRaw("RIGHT(REGEXP_REPLACE(invoice_end, '[^0-9]', ''), 7) = ?", [$last7FakturInvoice])
                                             ->orderBy('date', 'asc') // Ambil yang paling awal jika ada duplikat bersih
                                             ->first();
                    if (!$deposit) return null;

                    $date = Carbon::parse($deposit->date);
                    if ($date->day > 28) {
                        $date->addMonth();
                    }
                    return $date->format('F');
                })
                ->filter()
                ->groupBy(fn($month) => $month)
                ->map(fn($group, $month) => $month . ' (' . count($group) . ')')
                ->values()
                ->implode(', ');

            $lokSpkList = $faktur->transaksiJuals->pluck('lok_spk')->toArray();

            $returnBarang = ReturnBarang::whereIn('lok_spk', $lokSpkList)
                ->whereHas('returnModel', function ($query) use ($faktur) {
                    $query->whereDate('tgl_return', '>', $faktur->tgl_jual);
                })
                ->with('returnModel')
                ->get();

            $returnPerBulan = $returnBarang
                ->map(function ($item) {
                    $tgl = optional($item->returnModel)->tgl_return;
                    if (!$tgl) return null;

                    $date = Carbon::parse($tgl);
                    if ($date->day > 28) {
                        $date->addMonth();
                    }
                    return $date->format('F');
                })
                ->filter()
                ->groupBy(fn($month) => $month)
                ->map(fn($group, $month) => $month . ' (' . count($group) . ')')
                ->values()
                ->implode(', ');
            $return_info = $returnPerBulan ?: '-';


            return [
                'title' => '<a class="btn btn-info" href="' . route('transaksi-faktur-online.show', $faktur->id) . '" target="_blank">' . $title . '</a>',
                'tgl' => $tgl,
                'total_unit_faktur' => $total_unit_faktur,
                'total_nominal_faktur' => $total_nominal_faktur,
                'total_unit_invoice' => $total_unit_invoice,
                'total_unit_dibatalkan' => $total_unit_dibatalkan, // Data baru
                'total_uang_masuk' => $total_uang_masuk,
                'selisih' => $selisih,
                'keterangan' => $keterangan, // Logika baru
                'prefix' => substr($title, 0, 3),
                'bonusan' => $bonusPerBulan,
                'return_count' => $return_info,
            ];
        });

        // Filter status lunas/belum lunas
        if ($request->filled('cek')) {
            $status = $request->cek === 'Sudah_Dicek' ? 'Lunas' : 'Belum Lunas';
            $data = $data->filter(fn($item) => $item['keterangan'] === $status)->values();
        }

        return $data->sortBy('tgl')->values();
    }

}

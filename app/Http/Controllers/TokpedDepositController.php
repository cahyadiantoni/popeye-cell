<?php

namespace App\Http\Controllers;

use App\Models\ReturnBarang;
use Illuminate\Http\Request;
use App\Models\TokpedInputDeposit;
use App\Models\TokpedDataDeposit;
use App\Models\FakturOnline;
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
        logger('Filter params:', $request->all());
        logger('Data count:', [$data->count()]);
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

        $data = $fakturs->map(function ($faktur) {
            $title = $faktur->title;
            $tgl = $faktur->tgl_jual;
            $total_unit_faktur = $faktur->transaksiJuals->count();
            $total_nominal_faktur = $faktur->total;

            $invoices = $faktur->transaksiJuals->pluck('invoice')
                ->filter()
                ->unique()
                ->map(function ($invoice) {
                    // Hapus semua non-angka
                    $clean = preg_replace('/\D/', '', $invoice);
                    // Ambil 7 digit terakhir
                    return Str::substr($clean, -7);
                })
                ->toArray();

            $matchedInvoices = TokpedDataDeposit::where(function ($query) use ($invoices) {
                foreach ($invoices as $inv) {
                    $query->orWhereRaw("RIGHT(REGEXP_REPLACE(invoice_end, '[^0-9]', ''), 7) = ?", [$inv]);
                }
            })->pluck('invoice_end')->toArray();

            $total_unit_invoice = $faktur->transaksiJuals->filter(fn($item) => in_array($item->invoice, $matchedInvoices))->count();
            $total_uang_masuk = TokpedDataDeposit::whereIn('invoice_end', $invoices)->sum('nominal');
            $selisih = $total_nominal_faktur - $total_uang_masuk;

            $keterangan = $total_unit_faktur === $total_unit_invoice ? 'Lunas' : 'Belum Lunas';

            $bonusPerBulan = $faktur->transaksiJuals
                ->filter(fn($item) => in_array($item->invoice, $matchedInvoices))
                ->map(function ($item) {
                    $deposit = TokpedDataDeposit::where('invoice_end', $item->invoice)->first();
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
                        $date->addMonth(); // perlakuan sama seperti bonusan
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
                'total_uang_masuk' => $total_uang_masuk,
                'selisih' => $selisih,
                'keterangan' => $keterangan,
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

<?php

namespace App\Exports;

use App\Models\FakturOnline;
use App\Models\ReturnBarang;
use App\Models\TokpedDataDeposit;
use App\Models\TransaksiJualOnline;
use App\Models\TokpedDataOrder;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class FakturOnlineExport implements FromView, WithEvents
{
    protected $nomor_faktur;

    public function __construct($nomor_faktur)
    {
        $this->nomor_faktur = $nomor_faktur;
    }

    public function view(): View
    {
        $faktur = FakturOnline::with('barangs')->where('id', $this->nomor_faktur)->firstOrFail();
        
        $transaksiJualsOriginal = TransaksiJualOnline::with('barang')
            ->where('faktur_online_id', $this->nomor_faktur)
            ->get();
    
        $uniqueCleanedInvoices = $transaksiJualsOriginal->pluck('invoice')
            ->filter()
            ->map(function ($invoice) {
                $clean = preg_replace('/\D/', '', trim($invoice));
                return Str::substr($clean, -7);
            })
            ->unique()
            ->toArray();
    
        $uangMasukPerInvoice = [];
        if(!empty($uniqueCleanedInvoices)){
            $uangMasukPerInvoice = TokpedDataDeposit::where(function ($query) use ($uniqueCleanedInvoices) {
                foreach ($uniqueCleanedInvoices as $inv) {
                    $query->orWhereRaw("RIGHT(REGEXP_REPLACE(TRIM(invoice_end), '[^0-9]', ''), 7) = ?", [$inv]);
                }
            })
            ->selectRaw('invoice_end, SUM(nominal) as total_uang_masuk, MIN(date) as tanggal_masuk')
            ->groupBy('invoice_end')
            ->get()
            ->mapWithKeys(function ($item) {
                $cleanInvoiceEnd = preg_replace('/\D/', '', trim($item->invoice_end));
                $last7DigitsKey = Str::substr($cleanInvoiceEnd, -7);
                return [$last7DigitsKey => $item];
            });
        }

        // Ambil Tanggal Pembatalan
        $cancellationDatesPerInvoice = [];
        if (!empty($uniqueCleanedInvoices)) {
            $cancellationDatesPerInvoice = TokpedDataOrder::whereIn(DB::raw("RIGHT(REGEXP_REPLACE(TRIM(invoice_number), '[^0-9]', ''), 7)"), $uniqueCleanedInvoices)
                ->whereNotNull('cancelled_at')
                ->select('invoice_number', 'cancelled_at')
                ->get()
                ->mapWithKeys(function ($item) {
                    $cleanInvoiceNumber = preg_replace('/\D/', '', trim($item->invoice_number));
                    $last7DigitsKey = Str::substr($cleanInvoiceNumber, -7);
                    return [$last7DigitsKey => Carbon::parse($item->cancelled_at)->translatedFormat('j F Y')];
                });
        }
    
        $transaksiJuals = $transaksiJualsOriginal->sortBy(function ($item) use ($uangMasukPerInvoice) {
            if(empty($item->invoice)) return now()->addYears(100)->timestamp;
            $cleanItemInvoice = preg_replace('/\D/', '', trim($item->invoice));
            $lookupKey = Str::substr($cleanItemInvoice, -7);
            $tanggal = optional($uangMasukPerInvoice[$lookupKey] ?? null)->tanggal_masuk ?? now()->addYears(100);
            return Carbon::parse($tanggal)->timestamp;
        })->values();
    
        $transaksiJuals = $transaksiJuals->map(function ($trx) use ($faktur) {
            $returnBarang = ReturnBarang::where('lok_spk', $trx->lok_spk)
                ->with('returnModel')
                ->orderByDesc('id')
                ->first();
            if ($returnBarang && $returnBarang->returnModel && Carbon::parse($returnBarang->returnModel->tgl_return)->gt(Carbon::parse($faktur->tgl_jual))) {
                $trx->tgl_return = $returnBarang->returnModel->tgl_return;
            } else {
                $trx->tgl_return = null;
            }
            return $trx;
        });
        
        // Simpan data yang sudah diproses jika ingin menghindari re-fetch di AfterSheet
        // $this->processedData = compact('faktur', 'transaksiJuals', 'uangMasukPerInvoice', 'cancellationDatesPerInvoice');

        return view('exports.faktur-online', compact('faktur', 'transaksiJuals', 'uangMasukPerInvoice', 'cancellationDatesPerInvoice'));
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
    
                // Style untuk info section (A1:B7) - Asumsi view export memiliki 7 baris info
                $sheet->getStyle('A1:B7')->getFont()->setBold(true);
                // Anda mungkin perlu menyesuaikan warna dan border jika view 'exports.faktur-online' berbeda
                $sheet->getStyle('A1:A7')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFCE4D6');
                $sheet->getStyle('A1:B7')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                if ($sheet->getCell('B6')->getValue()) { 
                     $sheet->getStyle('B6')->getNumberFormat()->setFormatCode('"Rp."#,##0');
                }
    
                // Header row (sekarang A10:K10 karena ada tambahan Tgl Dibatalkan)
                $sheet->getStyle('A10:K10')->getFont()->setBold(true);
                $sheet->getStyle('A10:K10')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD9E1F2');
                $sheet->getStyle('A10:K10')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    
                // Auto size columns A sampai K
                foreach (range('A', 'K') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
    
                // Data processing (sebaiknya data dari $this->processedData jika sudah dioptimasi)
                // Untuk saat ini, kita ulangi pengambilan data seperti di view() method
                $faktur = FakturOnline::find($this->nomor_faktur); // Ambil faktur lagi
                $transaksiJualsOriginal = TransaksiJualOnline::with('barang')
                    ->where('faktur_online_id', $this->nomor_faktur)->get();
                $uniqueCleanedInvoices = $transaksiJualsOriginal->pluck('invoice')->filter()->map(function($inv){
                    return Str::substr(preg_replace('/\D/', '', trim($inv)), -7);
                })->unique()->toArray();

                $uangMasukPerInvoice = []; // Re-calculate for AfterSheet context
                if(!empty($uniqueCleanedInvoices)){
                    $uangMasukPerInvoice = TokpedDataDeposit::where(function ($query) use ($uniqueCleanedInvoices) {
                        foreach ($uniqueCleanedInvoices as $inv) {
                            $query->orWhereRaw("RIGHT(REGEXP_REPLACE(TRIM(invoice_end), '[^0-9]', ''), 7) = ?", [$inv]);
                        }
                    })->selectRaw('invoice_end, SUM(nominal) as total_uang_masuk, MIN(date) as tanggal_masuk')
                    ->groupBy('invoice_end')->get()->mapWithKeys(function($item){
                        return [Str::substr(preg_replace('/\D/', '', trim($item->invoice_end)), -7) => $item];
                    });
                }
                
                $cancellationDatesPerInvoice = []; // Re-calculate for AfterSheet context
                if (!empty($uniqueCleanedInvoices)) {
                    $cancellationDatesPerInvoice = TokpedDataOrder::whereIn(DB::raw("RIGHT(REGEXP_REPLACE(TRIM(invoice_number), '[^0-9]', ''), 7)"), $uniqueCleanedInvoices)
                        ->whereNotNull('cancelled_at')->select('invoice_number', 'cancelled_at')->get()
                        ->mapWithKeys(function ($item) {
                            $key = Str::substr(preg_replace('/\D/', '', trim($item->invoice_number)), -7);
                            return [$key => Carbon::parse($item->cancelled_at)->translatedFormat('j F Y')];
                        });
                }

                $transaksiJuals = $transaksiJualsOriginal->sortBy(function ($item) use ($uangMasukPerInvoice) {
                    if(empty($item->invoice)) return now()->addYears(100)->timestamp;
                    $key = Str::substr(preg_replace('/\D/', '', trim($item->invoice)), -7);
                    $tgl = optional($uangMasukPerInvoice[$key] ?? null)->tanggal_masuk ?? now()->addYears(100);
                    return Carbon::parse($tgl)->timestamp;
                })->values();
                
                $transaksiJuals = $transaksiJuals->map(function ($trx) use ($faktur) {
                     $returnBarang = ReturnBarang::where('lok_spk', $trx->lok_spk)
                        ->with('returnModel')->orderByDesc('id')->first();
                    if ($returnBarang && $returnBarang->returnModel && Carbon::parse($returnBarang->returnModel->tgl_return)->gt(Carbon::parse($faktur->tgl_jual))) {
                         $trx->tgl_return = $returnBarang->returnModel->tgl_return;
                    } else {
                         $trx->tgl_return = null;
                    }
                    return $trx;
                });

                $rowIndex = 11; // Data dimulai dari baris 11
                $no = 1;
                $startDataRow = $rowIndex;
    
                foreach ($transaksiJuals->groupBy('invoice') as $invoice => $items) {
                    $rowCount = count($items);
                    if ($rowCount == 0) continue;
    
                    $mergeStart = $rowIndex;
                    $mergeEnd = $rowIndex + $rowCount - 1;
    
                    $cleanedInvoiceKey = Str::substr(preg_replace('/\D/', '', trim($invoice)), -7);
                    $uangMasukData = $uangMasukPerInvoice[$cleanedInvoiceKey] ?? null;
                    $tglDibatalkanFormatted = $cancellationDatesPerInvoice[$cleanedInvoiceKey] ?? '-'; // Data baru
                    
                    $totalUangMasukVal = optional($uangMasukData)->total_uang_masuk ?? 0;
                    $tanggalMasukFormatted = ($uangMasukData && optional($uangMasukData)->tanggal_masuk) ? Carbon::parse($uangMasukData->tanggal_masuk)->translatedFormat('j F Y') : '-'; // Diubah
    
                    $sheet->mergeCells("B{$mergeStart}:B{$mergeEnd}");
                    $sheet->setCellValueExplicit("B{$mergeStart}", $invoice, DataType::TYPE_STRING);
    
                    $sheet->mergeCells("H{$mergeStart}:H{$mergeEnd}"); // Uang Masuk
                    $sheet->setCellValue("H{$mergeStart}", $totalUangMasukVal);
                    
                    $sheet->mergeCells("I{$mergeStart}:I{$mergeEnd}"); // Tanggal Masuk
                    $sheet->setCellValue("I{$mergeStart}", $tanggalMasukFormatted);

                    $sheet->mergeCells("J{$mergeStart}:J{$mergeEnd}"); // Tgl Dibatalkan (Kolom baru)
                    $sheet->setCellValue("J{$mergeStart}", $tglDibatalkanFormatted);
    
                    // Styling jika tidak ada uang masuk / tanggal masuk / tanggal dibatalkan
                    if (!$uangMasukData || $totalUangMasukVal == 0) {
                        $sheet->getStyle("H{$mergeStart}")->getFont()->getColor()->setARGB('FFFF0000');
                    }
                    if ($tanggalMasukFormatted === '-') {
                         $sheet->getStyle("I{$mergeStart}")->getFont()->getColor()->setARGB('FFFF0000');
                    }
                    if ($tglDibatalkanFormatted === '-') {
                        $sheet->getStyle("J{$mergeStart}")->getFont()->getColor()->setARGB('FFFF0000'); // Bisa juga warna lain
                    }
                    
                    foreach (['B', 'H', 'I', 'J'] as $col) { // Termasuk J
                         $sheet->getStyle("{$col}{$mergeStart}")->getAlignment()->setVertical('center')->setHorizontal('center');
                    }
    
                    foreach ($items as $itemIndex => $item) {
                        $currentRow = $mergeStart + $itemIndex;
                        $sheet->setCellValue("A{$currentRow}", $no++);
                        $sheet->setCellValue("C{$currentRow}", $item->lok_spk);
                        $sheet->setCellValue("D{$currentRow}", $item->barang->tipe ?? '-');
                        $sheet->setCellValue("E{$currentRow}", $item->harga);
                        $sheet->setCellValue("F{$currentRow}", $item->pj);
    
                        if ($item->pj == 0) {
                            $sheet->setCellValue("G{$currentRow}", '-');
                        } else {
                            $selisih = $item->harga - $item->pj;
                            $sheet->setCellValue("G{$currentRow}", $selisih);
                            $color = $selisih < 0 ? 'FFFF0000' : 'FF008000';
                            $sheet->getStyle("G{$currentRow}")->getFont()->getColor()->setARGB($color);
                        }
                        
                        // Kolom K untuk Tanggal Return
                        $tglReturnFormatted = $item->tgl_return ? Carbon::parse($item->tgl_return)->translatedFormat('j F Y') : '-';
                        $sheet->setCellValue("K{$currentRow}", $tglReturnFormatted);
                    }
                    $rowIndex += $rowCount;
                }
    
                $lastDataRow = $rowIndex - 1;
    
                if ($lastDataRow >= $startDataRow) {
                    $sheet->getStyle("A{$startDataRow}:K{$lastDataRow}") // Sampai K
                        ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    
                    foreach (['E', 'F', 'G', 'H'] as $col) { // Harga, PJ, Selisih, Uang Masuk
                        $sheet->getStyle("{$col}{$startDataRow}:{$col}{$lastDataRow}")
                            ->getNumberFormat()->setFormatCode('"Rp."#,##0');
                    }
                }
    
                $totalRowIndex = $rowIndex;
                $sheet->mergeCells("A{$totalRowIndex}:D{$totalRowIndex}");
                $sheet->setCellValue("A{$totalRowIndex}", 'TOTAL');
                $sheet->getStyle("A{$totalRowIndex}:D{$totalRowIndex}")->getFont()->setBold(true);
                $sheet->getStyle("A{$totalRowIndex}:D{$totalRowIndex}")->getAlignment()->setHorizontal('center');
    
                if ($lastDataRow >= $startDataRow) {
                    $sheet->setCellValue("E{$totalRowIndex}", "=SUM(E{$startDataRow}:E{$lastDataRow})");
                    $sheet->setCellValue("F{$totalRowIndex}", "=SUM(F{$startDataRow}:F{$lastDataRow})");
                    $sheet->setCellValue("G{$totalRowIndex}", "=SUM(G{$startDataRow}:G{$lastDataRow})");
                    $sheet->setCellValue("H{$totalRowIndex}", "=SUM(H{$startDataRow}:H{$lastDataRow})");
                } else {
                     $sheet->setCellValue("E{$totalRowIndex}", 0); // dst untuk F,G,H
                     $sheet->setCellValue("F{$totalRowIndex}", 0);
                     $sheet->setCellValue("G{$totalRowIndex}", 0);
                     $sheet->setCellValue("H{$totalRowIndex}", 0);
                }
    
                foreach (['E', 'F', 'G', 'H'] as $col) {
                    $sheet->getStyle("{$col}{$totalRowIndex}")->getFont()->setBold(true);
                    $sheet->getStyle("{$col}{$totalRowIndex}")->getNumberFormat()->setFormatCode('"Rp."#,##0');
                }
    
                $sheet->getStyle("A{$totalRowIndex}:K{$totalRowIndex}") // Sampai K
                    ->getFill()->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFEDEDED');
                $sheet->getStyle("A{$totalRowIndex}:K{$totalRowIndex}") // Sampai K
                    ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            }
        ];
    }
}

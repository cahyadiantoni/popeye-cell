<?php

namespace App\Exports;

use App\Models\FakturOnline;
use App\Models\ReturnBarang;
use App\Models\TokpedDataDeposit;
use App\Models\TransaksiJualOnline;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use Carbon\Carbon;

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
        $transaksiJuals = TransaksiJualOnline::with('barang')
            ->where('faktur_online_id', $this->nomor_faktur)
            ->orderBy('invoice')
            ->get();

        $invoiceList = $transaksiJuals->pluck('invoice')->unique();
        $uangMasukPerInvoice = TokpedDataDeposit::whereIn('invoice_end', $invoiceList)
            ->selectRaw('invoice_end, SUM(nominal) as total_uang_masuk, MIN(date) as tanggal_masuk')
            ->groupBy('invoice_end')
            ->get()
            ->keyBy('invoice_end');

        $transaksiJuals = $transaksiJuals->sortBy(function ($item) use ($uangMasukPerInvoice) {
            $tanggal = $uangMasukPerInvoice[$item->invoice]->tanggal_masuk ?? now()->addYears(100); // invoice tanpa data diletakkan di bawah
            return $tanggal;
        })->values(); // reset index agar urut

        $transaksiJuals = $transaksiJuals->map(function ($trx) {
            $returnBarang = ReturnBarang::where('lok_spk', $trx->lok_spk)
                ->with('returnModel') // pakai relasi
                ->orderByDesc('id')
                ->first();

            if ($returnBarang && $returnBarang->returnModel && $returnBarang->returnModel->tgl_return > $trx->t_jual) {
                $trx->tgl_return = $returnBarang->returnModel->tgl_return;
            } else {
                $trx->tgl_return = null;
            }

            return $trx;
        });

        return view('exports.faktur-online', compact('faktur', 'transaksiJuals', 'uangMasukPerInvoice'));
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;

                // Style for info section (A1:B7)
                $sheet->getStyle('A1:B7')->getFont()->setBold(true);
                $sheet->getStyle('A1:A7')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FCE4D6');
                $sheet->getStyle('A1:B7')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle('B6')->getNumberFormat()->setFormatCode('"Rp." #,##0');

                // Header row (A10:I10)
                $sheet->getStyle('A10:J10')->getFont()->setBold(true);
                $sheet->getStyle('A10:J10')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D9E1F2');
                $sheet->getStyle('A10:J10')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                // Auto size columns
                foreach (range('A', 'J') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                $rowIndex = 11;
                $no = 1;

                $faktur = FakturOnline::with('barangs')->where('id', $this->nomor_faktur)->firstOrFail();
                $transaksiJuals = TransaksiJualOnline::with('barang')
                    ->where('faktur_online_id', $this->nomor_faktur)
                    ->orderBy('invoice')
                    ->get();

                $invoiceList = $transaksiJuals->pluck('invoice')->unique();
                $uangMasukPerInvoice = TokpedDataDeposit::whereIn('invoice_end', $invoiceList)
                    ->selectRaw('invoice_end, SUM(nominal) as total_uang_masuk, MIN(date) as tanggal_masuk')
                    ->groupBy('invoice_end')
                    ->get()
                    ->keyBy('invoice_end');

                $transaksiJuals = $transaksiJuals->sortBy(function ($item) use ($uangMasukPerInvoice) {
                    $tanggal = $uangMasukPerInvoice[$item->invoice]->tanggal_masuk ?? now()->addYears(100); // invoice tanpa data diletakkan di bawah
                    return $tanggal;
                })->values(); // reset index agar urut

                $transaksiJuals = $transaksiJuals->map(function ($trx) {
                    $returnBarang = ReturnBarang::where('lok_spk', $trx->lok_spk)
                        ->with('returnModel') // pakai relasi
                        ->orderByDesc('id')
                        ->first();

                    if ($returnBarang && $returnBarang->returnModel && $returnBarang->returnModel->tgl_return > $trx->t_jual) {
                        $trx->tgl_return = $returnBarang->returnModel->tgl_return;
                    } else {
                        $trx->tgl_return = null;
                    }

                    return $trx;
                });

                foreach ($transaksiJuals->groupBy('invoice') as $invoice => $items) {
                    $rowCount = count($items);
                    $mergeStart = $rowIndex;
                    $mergeEnd = $rowIndex + $rowCount - 1;
                    $uangMasuk = $uangMasukPerInvoice[$invoice] ?? null;
                    $totalUangMasuk = $uangMasuk->total_uang_masuk ?? 0;
                    $tanggalMasuk = $uangMasuk ? Carbon::parse($uangMasuk->tanggal_masuk)->translatedFormat('d F Y') : 'Belum Ada Invoice';

                    // Merge Invoice (B), Uang Masuk (H), Tanggal Masuk (I)
                    $sheet->mergeCells("B{$mergeStart}:B{$mergeEnd}");
                    $sheet->mergeCells("H{$mergeStart}:H{$mergeEnd}");
                    $sheet->mergeCells("I{$mergeStart}:I{$mergeEnd}");

                    $sheet->setCellValueExplicit("B{$mergeStart}", $invoice, DataType::TYPE_STRING);
                    $sheet->setCellValue("H{$mergeStart}", $totalUangMasuk);
                    if ($totalUangMasuk == 0) {
                        $sheet->getStyle("H{$mergeStart}")->getFont()->getColor()->setRGB('FF0000');
                        $sheet->getStyle("I{$mergeStart}")->getFont()->getColor()->setRGB('FF0000');
                    }
                    $sheet->setCellValue("I{$mergeStart}", $tanggalMasuk);

                    // Align merged cells
                    foreach (['B', 'H', 'I'] as $col) {
                        $sheet->getStyle("{$col}{$mergeStart}:{$col}{$mergeEnd}")
                            ->getAlignment()->setVertical('center')->setHorizontal('center');
                    }

                    foreach ($items as $item) {
                        $sheet->setCellValue("A{$rowIndex}", $no++);
                        $sheet->setCellValue("C{$rowIndex}", $item->lok_spk);
                        $sheet->setCellValue("D{$rowIndex}", $item->barang->tipe ?? '-');
                        $sheet->setCellValue("E{$rowIndex}", $item->harga);
                        $sheet->setCellValue("F{$rowIndex}", $item->pj);

                        if ($item->pj == 0) {
                            $sheet->setCellValue("G{$rowIndex}", '-');
                        } else {
                            $selisih = $item->harga - $item->pj;
                            $sheet->setCellValue("G{$rowIndex}", $selisih);

                            $color = $selisih < 0 ? 'FF0000' : '008000';
                            $sheet->getStyle("G{$rowIndex}")->getFont()->getColor()->setRGB($color);
                        }

                        $rowIndex++;
                    }
                }

                // Border untuk data baris
                $sheet->getStyle("A10:J" . ($rowIndex - 1))
                    ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                // Format angka: kolom E, F, G, H
                foreach (['E', 'F', 'G', 'H'] as $col) {
                    $sheet->getStyle("{$col}11:{$col}" . ($rowIndex - 1))
                        ->getNumberFormat()->setFormatCode('"Rp." #,##0');
                }

                // Tambah baris total
                $sheet->mergeCells("A{$rowIndex}:D{$rowIndex}");
                $sheet->setCellValue("A{$rowIndex}", 'TOTAL');
                $sheet->getStyle("A{$rowIndex}:D{$rowIndex}")
                    ->getFont()->setBold(true);
                $sheet->getStyle("A{$rowIndex}:D{$rowIndex}")
                    ->getAlignment()->setHorizontal('center');

                // Formula jumlah di kolom E, F, G, H
                $sheet->setCellValue("E{$rowIndex}", "=SUM(E11:E" . ($rowIndex - 1) . ")");
                $sheet->setCellValue("F{$rowIndex}", "=SUM(F11:F" . ($rowIndex - 1) . ")");
                $sheet->setCellValue("G{$rowIndex}", "=SUM(G11:G" . ($rowIndex - 1) . ")");
                $sheet->setCellValue("H{$rowIndex}", "=SUM(H11:H" . ($rowIndex - 1) . ")");

                // Bold semua nilai total
                foreach (['E', 'F', 'G', 'H'] as $col) {
                    $sheet->getStyle("{$col}{$rowIndex}")->getFont()->setBold(true);
                }

                // Format angka total
                foreach (['E', 'F', 'G', 'H'] as $col) {
                    $sheet->getStyle("{$col}{$rowIndex}")
                        ->getNumberFormat()->setFormatCode('"Rp." #,##0');
                }

                // Warna background abu-abu terang
                $sheet->getStyle("A{$rowIndex}:H{$rowIndex}")
                    ->getFill()->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('EDEDED');

                // Border pada baris total
                $sheet->getStyle("A{$rowIndex}:H{$rowIndex}")
                    ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            }
        ];
    }
}

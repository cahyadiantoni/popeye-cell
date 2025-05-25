<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class RekapPerTokoSheet implements FromView, WithTitle, WithEvents, ShouldAutoSize
{
    protected $data;
    protected $prefix;

    public function __construct($data, $prefix)
    {
        $this->data = $data;
        $this->prefix = $prefix;
    }

    public function view(): View
    {
        return view('pages.tokped-deposit.export', [
            'data' => $this->data
        ]);
    }

    public function title(): string
    {
        return $this->getNamaToko();
    }

    protected function getNamaToko(): string
    {
        return match ($this->prefix) {
            'POD' => 'Podomoro',
            'PPY' => 'Popeye',
            'JJ-'  => 'Toko JJ',
            'NAR' => 'Naruto',
            default => 'Lain-lain'
        };
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;
                $rowCount = count($this->data) + 2;
                $totalRow = $rowCount + 1;

                // Header toko
                $sheet->mergeCells('A1:J1');
                $sheet->setCellValue('A1', strtoupper($this->getNamaToko()));
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14],
                    'alignment' => ['horizontal' => 'center'],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'D9E1F2']
                    ]
                ]);

                // Header tabel
                $sheet->getStyle('A2:J2')->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => ['horizontal' => 'center'],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'BDD7EE']
                    ],
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                    ]
                ]);

                // Border seluruh data + total
                $sheet->getStyle("A2:J{$totalRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                    ]
                ]);

                // Baris total
                $sheet->mergeCells("A{$totalRow}:B{$totalRow}");
                $sheet->setCellValue("A{$totalRow}", "TOTAL");
                $sheet->getStyle("C{$totalRow}:J{$totalRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E2EFDA']
                    ]
                ]);
                $sheet->getStyle("A{$totalRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => ['horizontal' => 'center'],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E2EFDA']
                    ]
                ]);

                // Format angka
                foreach (['D', 'F', 'G'] as $col) {
                    $sheet->getStyle("{$col}3:{$col}{$totalRow}")
                        ->getNumberFormat()
                        ->setFormatCode('"Rp."#,##0');
                }

                // Selisih negatif
                for ($row = 3; $row <= $rowCount; $row++) {
                    $value = $sheet->getCell("G{$row}")->getValue();
                    if (is_numeric($value) && $value < 0) {
                        $sheet->getStyle("G{$row}")->getFont()->getColor()->setRGB('FF0000');
                    }
                }

                // Status lunas/belum lunas
                for ($row = 3; $row <= $rowCount; $row++) {
                    $status = strtoupper((string) $sheet->getCell("H{$row}")->getValue());
                    if (str_contains($status, 'BELUM')) {
                        $sheet->getStyle("H{$row}")->getFont()->getColor()->setRGB('FF0000');
                    } elseif (str_contains($status, 'LUNAS')) {
                        $sheet->getStyle("H{$row}")->getFont()->getColor()->setRGB('00AA00');
                    }
                }
            }
        ];
    }
}

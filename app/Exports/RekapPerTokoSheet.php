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
        // Anda perlu memastikan view 'pages.tokped-deposit.export'
        // juga diperbarui untuk menampilkan kolom 'Total Unit Dibatalkan'
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
            'JJ-'  => 'Toko JJ', // Disesuaikan dari 'JJ-' menjadi 'JJ' jika prefix di controller adalah 'JJ'
            'NAR' => 'Naruto',
            default => 'Lain-lain'
        };
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;
                // Data dimulai dari baris ke-3 setelah header toko (baris 1) dan header tabel (baris 2)
                $rowCount = count($this->data) + 2; 
                $totalRow = $rowCount + 1;

                // Header toko - Sekarang dari A1 sampai K1
                $sheet->mergeCells('A1:K1'); 
                $sheet->setCellValue('A1', strtoupper($this->getNamaToko()));
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14],
                    'alignment' => ['horizontal' => 'center'],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'D9E1F2']
                    ]
                ]);

                // Header tabel - Sekarang dari A2 sampai K2
                $sheet->getStyle('A2:K2')->applyFromArray([
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

                // Border seluruh data + total - Sekarang dari A2 sampai K{$totalRow}
                $sheet->getStyle("A2:K{$totalRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                    ]
                ]);

                // Baris total
                $sheet->mergeCells("A{$totalRow}:B{$totalRow}"); // Ini tetap sama
                $sheet->setCellValue("A{$totalRow}", "TOTAL");
                // Style untuk kolom C sampai K di baris total
                $sheet->getStyle("C{$totalRow}:K{$totalRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E2EFDA']
                    ]
                ]);
                $sheet->getStyle("A{$totalRow}")->applyFromArray([ // Style untuk sel TOTAL (A-B merged)
                    'font' => ['bold' => true],
                    'alignment' => ['horizontal' => 'center'],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E2EFDA']
                    ]
                ]);

                // Format angka - Kolom bergeser
                // D: Total Faktur, G: Uang Masuk, H: Selisih
                foreach (['D', 'G', 'H'] as $col) { 
                    $sheet->getStyle("{$col}3:{$col}{$totalRow}")
                        ->getNumberFormat()
                        ->setFormatCode('"Rp."#,##0');
                }

                // Selisih negatif - Kolom bergeser ke H
                for ($row = 3; $row <= $rowCount; $row++) {
                    $value = $sheet->getCell("H{$row}")->getValue(); // Kolom G menjadi H
                    if (is_numeric($value) && $value < 0) {
                        $sheet->getStyle("H{$row}")->getFont()->getColor()->setRGB('FF0000');
                    }
                }

                // Status lunas/belum lunas - Kolom bergeser ke I
                for ($row = 3; $row <= $rowCount; $row++) {
                    $status = strtoupper((string) $sheet->getCell("I{$row}")->getValue()); // Kolom H menjadi I
                    if (str_contains($status, 'BELUM')) {
                        $sheet->getStyle("I{$row}")->getFont()->getColor()->setRGB('FF0000');
                    } elseif (str_contains($status, 'LUNAS')) {
                        $sheet->getStyle("I{$row}")->getFont()->getColor()->setRGB('00AA00');
                    }
                }
            }
        ];
    }
}
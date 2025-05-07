<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithDrawings;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Carbon\Carbon;

class SingleFakturSheet implements FromArray, WithTitle, WithHeadings, WithStyles, WithDrawings
{
    protected $faktur;
    protected $title;
    protected $drawings = [];

    public function __construct($faktur, $title)
    {
        $this->faktur = $faktur;
        $this->title = $title;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function headings(): array
    {
        return [
            ['Nomor Faktur', $this->faktur->nomor_faktur],
            ['Pembeli', $this->faktur->pembeli],
            ['Tanggal Penjualan', Carbon::parse($this->faktur->tgl_jual)->format('d-m-Y')],
            ['Petugas', $this->faktur->petugas],
            ['Grade', $this->faktur->grade],
            ['Keterangan', $this->faktur->keterangan],
            [], // empty row for spacing
            ['No', 'Lok Spk', 'Merk Tipe', 'Harga', 'Sub Total'], // Item table header
        ];
    }

    public function array(): array
    {
        $rows = [];

        // Add items
        foreach ($this->faktur->transaksiJuals as $index => $t) {
            $rows[] = [
                $index + 1,
                $t->lok_spk,
                $t->barang->tipe ?? '-',
                $t->harga, // Keep original value
                $t->subtotal, // Keep original value
            ];
        }

        $rows[] = ['Total Harga Keseluruhan', '', '', '', $this->faktur->totalHarga];

        // Add proof of transfer section
        if (!$this->faktur->bukti->isEmpty()) {
            $rows[] = [''];
            $rows[] = [''];
            $rows[] = ['--- Bukti Transfer ---'];
            foreach ($this->faktur->bukti as $index => $bukti) {
                $rows[] = [
                    'Bukti ' . ($index + 1),
                    'Keterangan: ' . ($bukti->keterangan ?? '-'),
                    'Nominal: Rp' . number_format($bukti->nominal, 0, ',', '.'),
                ];
            }
        }

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        // Header styles (invoice info) - Blue background
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 12],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '2F75B5']],
            'borders' => [
                'outline' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];

        // Apply header style to the first 6 rows (invoice info)
        for ($i = 1; $i <= 6; $i++) {
            $sheet->getStyle('A'.$i.':B'.$i)->applyFromArray($headerStyle);
            $sheet->getStyle('A'.$i.':B'.$i)->getAlignment()->setVertical('center');
        }

        // Item table header style - Green background
        $itemHeaderStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 12],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '70AD47']],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
            'alignment' => ['vertical' => 'center'],
        ];

        // Apply item header style (row 8)
        $sheet->getStyle('A8:E8')->applyFromArray($itemHeaderStyle);

        // Item table body style
        $itemBodyStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
            'alignment' => ['vertical' => 'center'],
        ];

        // Determine how many items we have
        $itemCount = count($this->faktur->transaksiJuals);
        if ($itemCount > 0) {
            $sheet->getStyle('A9:E'.(9 + $itemCount - 1))->applyFromArray($itemBodyStyle);
            
            // Format number columns as currency without changing the value
            $sheet->getStyle('D9:E'.(9 + $itemCount + 2))->getNumberFormat()->setFormatCode('"Rp"#,##0;-"Rp"#,##0');
        }

        // Total row style - Bold with gray background
        $totalRow = 9 + $itemCount;
        $sheet->getStyle('A'.$totalRow.':E'.$totalRow)->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '000000']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9D9D9']],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
            'alignment' => ['vertical' => 'center'],
        ]);

        // Merge cells for total label
        $sheet->mergeCells('A'.$totalRow.':D'.$totalRow);
        $sheet->getStyle('A'.$totalRow)->getAlignment()->setHorizontal('center');
        $sheet->getStyle('E'.$totalRow)->getAlignment()->setHorizontal('right');

        // Proof of transfer header style - Dark red background
        if (!$this->faktur->bukti->isEmpty()) {
            $proofHeaderRow = $totalRow + 3;
            $sheet->getStyle('A'.$proofHeaderRow.':C'.$proofHeaderRow)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 12],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C00000']],
                'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
            ]);
            $sheet->mergeCells('A'.$proofHeaderRow.':C'.$proofHeaderRow);

            // Proof details style - Light orange background
            $proofDetailsStyle = [
                'font' => ['bold' => true],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8CBAD']],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
                'alignment' => ['vertical' => 'center', 'wrapText' => true],
            ];

            $proofStartRow = $proofHeaderRow + 1;
            foreach ($this->faktur->bukti as $index => $bukti) {
                $sheet->getStyle('A'.$proofStartRow.':C'.$proofStartRow)->applyFromArray($proofDetailsStyle);
                $proofStartRow += 3; // Move to next proof (each proof has 3 rows: details + 2 empty)
            }
        }

        // Auto-size columns
        foreach (range('A', 'E') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Set row heights for better appearance
        $sheet->getRowDimension(8)->setRowHeight(25); // Item header row
        $sheet->getRowDimension($totalRow)->setRowHeight(25); // Total row
        if (!$this->faktur->bukti->isEmpty()) {
            $sheet->getRowDimension($totalRow + 3)->setRowHeight(25); // Proof header row
        }

        return [];
    }

    public function drawings()
    {
        $drawings = [];
        $itemCount = count($this->faktur->transaksiJuals);
        $startRow = 9 + $itemCount + 6; // Starting row for proof images
        
        if (!$this->faktur->bukti->isEmpty()) {
            foreach ($this->faktur->bukti as $index => $bukti) {
                if (file_exists(storage_path('app/public/' . $bukti->foto))) {
                    $drawing = new Drawing();
                    $drawing->setName('Bukti ' . ($index + 1));
                    $drawing->setDescription('Bukti Transfer');
                    $drawing->setPath(storage_path('app/public/' . $bukti->foto));
                    $drawing->setHeight(500);
                    $drawing->setCoordinates('B' . ($startRow + ($index * 4))); // 4 rows per proof (header + details + image + space)
                    $drawing->setOffsetX(10);
                    $drawing->setOffsetY(10);
                    $drawings[] = $drawing;
                }
            }
        }
        
        return $drawings;
    }
}
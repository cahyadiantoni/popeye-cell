<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class HargaPerGradeSheetExport implements FromView, WithTitle, WithStyles
{
    protected $dataPivot;
    protected $tanggalHeaders;
    protected $grade;

    public function __construct(string $grade, $dataPivot, $tanggalHeaders)
    {
        $this->grade = $grade;
        $this->dataPivot = $dataPivot;
        $this->tanggalHeaders = $tanggalHeaders;
    }

    /**
     * Mengembalikan view yang akan dirender menjadi sheet Excel.
     */
    public function view(): View
    {
        return view('exports.master-harga-pivot', [
            'dataPivot' => $this->dataPivot,
            'tanggalHeaders' => $this->tanggalHeaders
        ]);
    }

    /**
     * Memberikan judul untuk sheet ini.
     */
    public function title(): string
    {
        // Mengganti karakter yang tidak valid untuk nama sheet dengan spasi
        return preg_replace('/[\\\\?*:\\[\\]\/]/', ' ', $this->grade);
    }

    /**
     * Menerapkan styling pada sheet.
     */
    public function styles(Worksheet $sheet)
    {
        // Ambil header range (misal: A1 sampai Z1)
        $headerRange = 'A1:' . $sheet->getHighestDataColumn() . '1';

        // Styling untuk header: Bold dan background warna
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getFill()
              ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
              ->getStartColor()->setARGB('FFD3D3D3'); // Warna abu-abu muda

        // Styling untuk seluruh tabel: Beri border
        $tableRange = 'A1:' . $sheet->getHighestDataColumn() . $sheet->getHighestDataRow();
        $sheet->getStyle($tableRange)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
    
        // Otomatis sesuaikan lebar kolom
        foreach (range('A', $sheet->getHighestDataColumn()) as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }
    }
}
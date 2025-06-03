<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class PulsaReportSummarySheet implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{
    protected $reportCollection;
    protected $summaryData; // Untuk menyimpan data rangkuman

    public function __construct($reportCollection)
    {
        $this->reportCollection = $reportCollection;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $this->summaryData = $this->reportCollection->groupBy(function ($item) {
            $kode = $item->kode_master_match ?? 'TIDAK ADA KODE';
            $nama = $item->nama_toko_master_match ?? 'TIDAK ADA NAMA TOKO';
            // Pastikan $item->Cabang adalah string untuk match
            $cabangValue = (string) $item->Cabang;
            $tipeTransaksi = match ($cabangValue) {
                '0000' => 'PAM',
                '0001' => 'PASCABAYAR',
                '0253' => 'TOKEN',
                '0998' => 'PULSA',
                default => $cabangValue, // Atau '-'
            };
            return $kode . '|' . $nama . '|' . $tipeTransaksi; // Kunci grup komposit
        })->map(function ($items, $key) {
            list($kodeToko, $namaToko, $tipeTransaksi) = explode('|', $key);
            
            return [
                'Kode Toko'           => $kodeToko === 'TIDAK ADA KODE' ? '-' : $kodeToko,
                'Nama Toko'           => $namaToko === 'TIDAK ADA NAMA TOKO' ? '-' : $namaToko,
                'Tipe Transaksi'      => $tipeTransaksi,
                'Jumlah Transaksi'    => $items->count(),
                'Jumlah Total (Uang)' => $items->sum('Jumlah'),
            ];
        })->sortBy(['Kode Toko', 'Nama Toko', 'Tipe Transaksi'])->values();

        return $this->summaryData;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Kode Toko',
            'Nama Toko',
            'Tipe Transaksi',
            'Jumlah Transaksi',
            'Jumlah Total (Uang)',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $headerRange = 'A1:E1'; // 5 Kolom

                // Style Header
                $sheet->getStyle($headerRange)->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF203764']], // Biru lebih gelap
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                ]);
                
                $numRows = $this->summaryData->count() + 1;
                if ($numRows > 1) { // Hanya jika ada data rangkuman
                    $tableRange = 'A1:E' . $numRows;
                    $sheet->getStyle($tableRange)->applyFromArray([
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF000000']],
                        ],
                    ]);

                    // Format kolom Jumlah Total (Uang) - Kolom E
                    $sheet->getStyle('E2:E' . $numRows)->getNumberFormat()
                          ->setFormatCode('"Rp."#,##0.00');
                    // Format kolom Jumlah Transaksi (Kolom D) sebagai angka biasa
                    $sheet->getStyle('D2:D' . $numRows)->getNumberFormat()
                          ->setFormatCode(NumberFormat::FORMAT_NUMBER);
                }
            }
        ];
    }
}
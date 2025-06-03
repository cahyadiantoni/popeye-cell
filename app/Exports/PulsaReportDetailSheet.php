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
use Carbon\Carbon;

class PulsaReportDetailSheet implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{
    protected $reportCollection;

    public function __construct($reportCollection)
    {
        $this->reportCollection = $reportCollection;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        // Format data agar sesuai dengan tampilan di view dan kebutuhan export
        return $this->reportCollection->map(function ($row) {
            $cabangValue = (string) $row->Cabang;
            $tipeTransaksi = match ($cabangValue) {
                '0000' => 'PAM',
                '0001' => 'PASCABAYAR',
                '0253' => 'TOKEN',
                '0998' => 'PULSA',
                default => $cabangValue,
            };

            return [
                'Tanggal'           => Carbon::parse($row->Tanggal)->translatedFormat('d M Y'),
                'Kode'              => $row->kode_master_match ?? '-',
                'Nama Toko'         => $row->nama_toko_master_match ?? '-',
                'Transaksi'         => $tipeTransaksi,
                'Keterangan'        => $row->Keterangan,
                'Cabang'            => $row->Cabang,
                'Jumlah'            => $row->Jumlah, // Biarkan angka untuk formatting
                'Jenis'             => $row->Jenis,
                'Saldo'             => $row->Saldo,  // Biarkan angka untuk formatting
            ];
        });
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Tanggal',
            'Kode',
            'Nama Toko',
            'Transaksi',
            'Keterangan',
            'Cabang',
            'Jumlah',
            'Jenis',
            'Saldo',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $headerRange = 'A1:I1'; // Sesuaikan dengan jumlah kolom (9 kolom)

                // Style Header
                $sheet->getStyle($headerRange)->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF4F81BD']],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                ]);

                // Border untuk seluruh tabel
                $numRows = $this->reportCollection->count() + 1;
                $tableRange = 'A1:I' . $numRows;
                $sheet->getStyle($tableRange)->applyFromArray([
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF000000']],
                    ],
                ]);

                // Format kolom Jumlah (kolom G) dan Saldo (kolom I) sebagai mata uang Rupiah
                // Data dimulai dari baris ke-2
                if ($numRows > 1) { // Hanya jika ada data
                    $sheet->getStyle('G2:G' . $numRows)->getNumberFormat()
                          ->setFormatCode('"Rp."#,##0.00');
                    $sheet->getStyle('I2:I' . $numRows)->getNumberFormat()
                          ->setFormatCode('"Rp."#,##0.00');
                }
            }
        ];
    }
}
<?php

namespace App\Exports;

use App\Models\PulsaMaster;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents; // Tambahkan ini
use Maatwebsite\Excel\Events\AfterSheet;  // Tambahkan ini
use PhpOffice\PhpSpreadsheet\Style\Fill;   // Tambahkan ini
use PhpOffice\PhpSpreadsheet\Style\Border; // Tambahkan ini

class PulsaMasterTemplateExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents // Implement WithEvents
{
    protected $data; // Untuk menyimpan koleksi agar bisa dihitung di AfterSheet

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        // Mengambil semua data dengan urutan kolom yang sesuai untuk template
        $this->data = PulsaMaster::select(
            'kode',
            'nama_toko',
            'pasca_bayar1',
            'pasca_bayar2',
            'token1',
            'token2',
            'pam1',
            'pam2',
            'pulsa1',
            'pulsa2',
            'pulsa3'
        )->get();
        return $this->data;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        // Mendefinisikan header kolom sesuai dengan field di tabel pulsa_master
        return [
            'kode',
            'nama_toko',
            'pasca_bayar1',
            'pasca_bayar2',
            'token1',
            'token2',
            'pam1',
            'pam2',
            'pulsa1',
            'pulsa2',
            'pulsa3',
        ];
    }

    /**
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate(); // Dapatkan instance PhpSpreadsheet

                // --- Style untuk Header ---
                $headerRange = 'A1:K1'; // Asumsi ada 11 kolom (A sampai K)
                $sheet->getStyle($headerRange)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['argb' => 'FFFFFFFF'], // Warna font putih
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FF4F81BD'], // Warna latar biru tua (contoh)
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    ],
                ]);

                // --- Style untuk Border Seluruh Tabel ---
                $numRows = $this->data->count() + 1; // +1 untuk baris header
                $highestColumn = 'K'; // Kolom terakhir (kolom ke-11)
                $tableRange = 'A1:' . $highestColumn . $numRows;

                $sheet->getStyle($tableRange)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => 'FF000000'], // Warna border hitam
                        ],
                    ],
                ]);

                // ShouldAutoSize sudah diimplementasikan, jadi lebar kolom akan otomatis.
                // Jika ingin lebih spesifik, bisa diatur di sini juga per kolom.
                // Contoh: $sheet->getColumnDimension('A')->setWidth(20);
            }
        ];
    }
}
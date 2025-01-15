<?php

namespace App\Exports;

use App\Models\KirimBarang;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TerimaBarangExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    protected $kirimId;

    // Constructor untuk menerima $id
    public function __construct($kirimId)
    {
        $this->kirimId = $kirimId;
    }

    public function collection()
    {
        return KirimBarang::with('barang')
            ->where('kirim_id', $this->kirimId)
            ->get()
            ->map(function ($barang, $index) {
                return [
                    'no' => $index + 1, // Tambahkan nomor increment
                    'lok_spk' => $barang->lok_spk,
                    'tipe_barang' => $barang->barang->tipe ?? '-',
                ];
            });
    }

    public function headings(): array
    {
        return [
            'No', // Tambahkan header untuk nomor
            'Lok SPK',
            'Tipe Barang',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Hitung jumlah baris dengan data (termasuk header)
        $rowCount = KirimBarang::where('kirim_id', $this->kirimId)->count() + 1;

        // Terapkan gaya hanya pada cell yang memiliki data
        $dataRange = "A1:C{$rowCount}";

        return [
            1 => [ // Gaya header
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4CAF50'], // Warna hijau
                ],
            ],
            $dataRange => [ // Border hanya untuk cell dengan data
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ],
        ];
    }
}

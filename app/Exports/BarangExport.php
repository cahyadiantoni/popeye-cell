<?php

namespace App\Exports;

use App\Models\Barang;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BarangExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    protected $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function collection()
    {
        return Barang::with('gudang')
            ->where('gudang_id', $this->id)
            ->where('status_barang', 1)
            ->get()
            ->map(function ($barang, $index) {
                return [
                    'no' => $index + 1, // Tambahkan nomor urut
                    'lok_spk' => $barang->lok_spk,
                    'jenis' => $barang->jenis,
                    'tipe' => $barang->tipe,
                    'grade' => $barang->grade,
                    'kelengkapan' => $barang->kelengkapan,
                    'gudang' => $barang->gudang->nama_gudang ?? 'N/A',
                ];
            });
    }

    public function headings(): array
    {
        return ['No', 'LOK_SPK', 'Jenis', 'Tipe', 'Grade', 'Kelengkapan', 'Gudang'];
    }

    public function styles(Worksheet $sheet)
    {
        $rowCount = Barang::where('gudang_id', $this->id)->where('status_barang', 1)->count() + 1;
        $dataRange = "A1:G{$rowCount}";

        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4CAF50'],
                ],
            ],
            $dataRange => [
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

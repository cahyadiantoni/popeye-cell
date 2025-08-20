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

    public function __construct($id, $jenis = null)
    {
        $this->id = $id;
        $this->jenis = $jenis;
    }

    public function collection()
    {
        $query = Barang::with('gudang')
            ->whereIn('status_barang', [1, 5]);

        if ($this->id !== 'all') {
            $query->where('gudang_id', $this->id);
        }

        if ($this->jenis) {
            $query->where('jenis', $this->jenis);
        }

        return $query->get()->map(function ($barang, $index) {
            return [
                'no' => $index + 1,
                'lok_spk' => $barang->lok_spk,
                'jenis' => $barang->jenis,
                'tipe' => $barang->tipe,
                'imei' => $barang->imei,
                'grade' => $barang->grade,
                'kelengkapan' => $barang->kelengkapan,
                'gudang' => $barang->gudang->nama_gudang ?? 'N/A',
            ];
        });
    }

    public function headings(): array
    {
        return ['No', 'LOK_SPK', 'Jenis', 'Tipe', 'Imei', 'Grade', 'Kelengkapan', 'Gudang'];
    }

    public function styles(Worksheet $sheet)
    {
        $rowCount = Barang::where('gudang_id', $this->id)->whereIn('status_barang', [1, 5])->count() + 1;
        $dataRange = "A1:H{$rowCount}";

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

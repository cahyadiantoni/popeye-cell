<?php

namespace App\Exports;

use App\Models\AdmReqTokped;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ReqTokpedExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize, WithEvents
{
    protected $filters;

    public function __construct($filters)
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = AdmReqTokped::with(['user', 'items.item'])->orderBy('status', 'asc')->orderBy('tgl', 'asc');

        if (!is_null($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }
        if (!is_null($this->filters['start_date'])) {
            $query->whereDate('tgl', '>=', $this->filters['start_date']);
        }
        if (!is_null($this->filters['end_date'])) {
            $query->whereDate('tgl', '<=', $this->filters['end_date']);
        }

        $data = collect();
        $query->get()->each(function ($todo, $index) use ($data) {
            foreach ($todo->items as $item) {
                $data->push([
                    'No'           => $index + 1,
                    'Tanggal'      => \Carbon\Carbon::parse($todo->tgl)->format('d/m/Y'),
                    'Kode Lokasi'  => $todo->kode_lok,
                    'Nama Toko'    => $todo->nama_toko,
                    'Nama AM'      => $todo->user->name ?? '-',
                    'Nama Barang'  => $item->item->name ?? '-',
                    'Lain Lain'    => $item->nama_barang ?? '-',
                    'Quantity'     => $item->quantity, // Tidak dijumlahkan
                    'Alasan'       => $todo->alasan,
                    'Status'       => $this->getStatusLabel($todo->status),
                ]);
            }
        });
        
        return $data;
    }

    public function headings(): array
    {
        return ['No', 'Tanggal', 'Kode Lokasi', 'Nama Toko', 'Nama AM', 'Nama Barang', 'Lain Lain', 'Quantity', 'Alasan', 'Status'];
    }

    private function getStatusLabel($status)
    {
        $statuses = [
            0 => 'Draft',
            1 => 'Terkirim',
            2 => 'Revisi',
            3 => 'Ditolak',
            4 => 'Proses Tokped',
            5 => 'Sudah Dikirim',
        ];

        return $statuses[$status] ?? 'Unknown';
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:J1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4CAF50']
            ],
        ]);

        $sheet->getStyle('A1:J' . $sheet->getHighestRow())->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);
    }

    public function registerEvents(): array
    {
        return [
            \Maatwebsite\Excel\Events\AfterSheet::class => function (\Maatwebsite\Excel\Events\AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                for ($row = 2; $row <= $highestRow; $row++) {
                    $status = $sheet->getCell("J$row")->getValue();

                    $color = match ($status) {
                        'Draft'            => '6C757D',
                        'Terkirim'         => '0D6EFD',
                        'Revisi'           => 'FFC107',
                        'Ditolak'          => 'DC3545',
                        'Proses Tokped'    => '0DCAF0',
                        'Sudah Dikirim'    => '198754',
                        default            => 'E0E0E0',
                    };

                    $sheet->getStyle("J$row")->applyFromArray([
                        'font' => ['color' => ['rgb' => 'FFFFFF']],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => $color]
                        ],
                    ]);
                }
            }
        ];
    }
}

<?php

namespace App\Exports;

use App\Models\AdmTodoTf;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class TodoTransferExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize, WithEvents
{
    protected $filters;

    public function __construct($filters)
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = AdmTodoTf::with('user')->orderBy('status', 'asc')->orderBy('tgl', 'asc'); // Urutkan berdasarkan status

        if (!is_null($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }
        if (!is_null($this->filters['start_date'])) {
            $query->whereDate('tgl', '>=', $this->filters['start_date']);
        }
        if (!is_null($this->filters['end_date'])) {
            $query->whereDate('tgl', '<=', $this->filters['end_date']);
        }

        return $query->get()->map(function ($todo, $index) {
            return [
                'No'           => $index + 1,
                'Tanggal'      => \Carbon\Carbon::parse($todo->tgl)->format('d/m/Y'),
                'Kode Lokasi'  => $todo->kode_lok,
                'Nama Toko'    => $todo->nama_toko,
                'Nama AM'      => $todo->user->name ?? '-',
                'Keterangan'   => $todo->keterangan,
                'Bank'         => $todo->bank,
                'Nomor Rekening' => $todo->no_rek,
                'Nama Rekening Tujuan' => $todo->nama_rek,
                'Nominal'      => $todo->nominal, 
                'Status'       => $this->getStatusLabel($todo->status),
            ];
        });
    }

    public function headings(): array
    {
        return ['No', 'Tanggal', 'Kode Lokasi', 'Nama Toko', 'Nama AM', 'Keterangan', 'Bank', 'Nomor Rekening', 'Nama Rekening Tujuan', 'Nominal', 'Status'];
    }

    /**
     * Konversi status ke teks yang sesuai
     */
    private function getStatusLabel($status)
    {
        $statuses = [
            0 => 'Draft',
            1 => 'Terkirim',
            2 => 'Revisi',
            3 => 'Ditolak',
            4 => 'Proses Transfer',
            5 => 'Sudah Ditransfer',
        ];

        return $statuses[$status] ?? 'Unknown';
    }

    /**
     * Styling Header
     */
    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:K1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4CAF50'] // Warna hijau untuk header
            ],
        ]);

        // Buat garis tabel
        $sheet->getStyle('A1:K' . $sheet->getHighestRow())->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);
    }

    /**
     * Event untuk memberi warna pada baris berdasarkan status
     */
    public function registerEvents(): array
    {
        return [
            \Maatwebsite\Excel\Events\AfterSheet::class => function (\Maatwebsite\Excel\Events\AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                for ($row = 2; $row <= $highestRow; $row++) {
                    $status = $sheet->getCell("K$row")->getValue(); // Ambil nilai kolom status

                    // Tentukan warna berdasarkan status (sesuai dengan Bootstrap di Blade)
                    $color = match ($status) {
                        'Draft'            => '6C757D', // bg-secondary (abu-abu)
                        'Terkirim'         => '0D6EFD', // bg-primary (biru)
                        'Revisi'           => 'FFC107', // bg-warning text-dark (kuning)
                        'Ditolak'          => 'DC3545', // bg-danger (merah)
                        'Proses Transfer'  => '0DCAF0', // bg-info text-dark (biru muda)
                        'Sudah Ditransfer' => '198754', // bg-success (hijau)
                        default            => 'E0E0E0', // Default abu-abu terang
                    };

                    // Terapkan warna latar belakang pada kolom status
                    $sheet->getStyle("K$row")->applyFromArray([
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

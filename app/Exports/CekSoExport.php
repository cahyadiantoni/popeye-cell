<?php

namespace App\Exports;

use App\Models\CekSO;
use App\Models\CekSOBarang;
use App\Models\CekSOFinished;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CekSoExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, ShouldAutoSize
{
    protected $cekso;

    public function __construct(CekSO $cekso)
    {
        $this->cekso = $cekso;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection(): Collection
    {
        // Jika SO sudah selesai, ambil data dari tabel 'finished'
        if ($this->cekso->is_finished) {
            return CekSOFinished::where('t_cek_so_id', $this->cekso->id)
                ->leftJoin('t_barang', function ($join) {
                    $join->on('t_barang.lok_spk', '=', 't_cek_so_finished.lok_spk')
                         ->where('t_barang.gudang_id', '=', $this->cekso->gudang_id);
                })
                ->select(
                    't_cek_so_finished.lok_spk', 't_cek_so_finished.status', 
                    't_cek_so_finished.petugas_scan', 't_cek_so_finished.lokasi',
                    't_barang.jenis', 't_barang.tipe', 't_barang.kelengkapan'
                )
                ->orderBy('t_cek_so_finished.status')
                ->get();
        }
        
        // Jika SO belum selesai, kita perlu membangun koleksi secara manual
        $scannedItems = CekSOBarang::where('t_cek_so_id', $this->cekso->id)->get()->keyBy('lok_spk');
        $allMasterItems = \App\Models\Barang::where('gudang_id', $this->cekso->gudang_id)
            ->where('status_barang', 1)->get()->keyBy('lok_spk');

        $allItems = $scannedItems->union($allMasterItems);

        return $allItems->map(function ($item, $lok_spk) use ($scannedItems, $allMasterItems) {
            $scanned = $scannedItems->get($lok_spk);
            $master = $allMasterItems->get($lok_spk);
            
            $status = 0; // Default Belum Discan
            if ($scanned) {
                $status = $scanned->status; // 1, 3, atau 4
                if (!$master) {
                    $status = 2; // Tidak ada di DB
                }
            }

            return (object) [
                'lok_spk' => $lok_spk,
                'status' => $status,
                'petugas_scan' => $scanned->petugas_scan ?? '-',
                'lokasi' => $scanned->lokasi ?? '-',
                'jenis' => $master->jenis ?? '-',
                'tipe' => $master->tipe ?? '-',
                'kelengkapan' => $master->kelengkapan ?? '-',
            ];
        });
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'No',
            'LOK_SPK',
            'Jenis',
            'Tipe',
            'Kelengkapan',
            'Status',
            'Petugas Scan',
            'Lokasi',
        ];
    }

    /**
     * @param mixed $row
     * @return array
     */
    public function map($row): array
    {
        static $index = 0;
        $index++;

        switch ($row->status) {
            case 0: $statusText = 'Belum Discan'; break;
            case 1: $statusText = 'Scan Sistem'; break;
            case 2: $statusText = 'Tidak Ada di DB'; break;
            case 3: $statusText = 'Input Manual'; break;
            case 4: $statusText = 'Upload Excel'; break;
            default: $statusText = 'Tidak Diketahui'; break;
        }

        return [
            $index,
            $row->lok_spk,
            $row->jenis ?? '-',
            $row->tipe ?? '-',
            $row->kelengkapan ?? '-',
            $statusText,
            $row->petugas_scan ?? '-',
            $row->lokasi ?? '-',
        ];
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'Detail Cek SO - ' . $this->cekso->kode;
    }
    
    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet): array
    {
        // Membuat heading menjadi bold
        $sheet->getStyle('A1:H1')->getFont()->setBold(true);

        // Menambahkan border ke seluruh tabel
        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle('A1:H'.$lastRow)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        return [];
    }
}
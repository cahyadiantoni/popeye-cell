<?php

namespace App\Exports;

// Gunakan model FakturBawah-mu
use App\Models\FakturBawah; 
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Carbon\Carbon;

class FakturBawahGabunganExport implements FromCollection, WithTitle, WithHeadings, WithStyles, ShouldAutoSize
{
    protected $fakturs;

    /**
     * Menerima koleksi faktur dari controller
     */
    public function __construct(Collection $fakturs)
    {
        $this->fakturs = $fakturs;
    }

    /**
     * Judul untuk sheet
     */
    public function title(): string
    {
        return 'Gabungan Faktur Bawah';
    }

    /**
     * Mendefinisikan header tabel sesuai permintaanmu
     */
    public function headings(): array
    {
        return [
            'No',
            'Tanggal',
            'Nomor Faktur',
            'Keterangan',
            'Lok Spk',
            'Merek Tipe',
            'Harga'
        ];
    }

    /**
     * Mengubah data koleksi Eloquent menjadi array untuk Excel.
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $rows = new Collection();
        $no = 1;

        // Loop setiap faktur (yang sudah diurutkan berdasarkan tgl_jual ASC)
        foreach ($this->fakturs as $faktur) {
            
            // Loop setiap barang di dalam faktur tersebut
            // Ini memastikan barang dari faktur yang sama tidak akan terpencar
            foreach ($faktur->transaksiJuals as $transaksi) {
                
                $rows->push([
                    'No'           => $no++,
                    'Tanggal'      => Carbon::parse($faktur->tgl_jual)->format('d-m-Y'),
                    'Nomor Faktur' => $faktur->nomor_faktur,
                    'Keterangan'   => $faktur->keterangan, // Keterangan dari faktur
                    'Lok Spk'      => $transaksi->lok_spk,
                    'Merek Tipe'   => $transaksi->barang->tipe ?? '-', // Ambil tipe dari relasi barang
                    'Harga'        => $transaksi->harga, // Harga barang
                ]);
            }
        }

        return $rows;
    }

    /**
     * Menerapkan style pada sheet
     */
    public function styles(Worksheet $sheet)
    {
        // Style untuk Header (Baris 1) - Warna Hijau (dari referensimu)
        $itemHeaderStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 12],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '70AD47']], // Kode warna hijau
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
            'alignment' => ['vertical' => 'center'],
        ];
        
        // Terapkan style HANYA ke baris 1 (header)
        $sheet->getStyle('A1:G1')->applyFromArray($itemHeaderStyle);
        $sheet->getRowDimension(1)->setRowHeight(25); // Atur tinggi baris header

        // Style untuk Body (semua baris setelah header)
        $lastRow = $sheet->getHighestRow();
        if ($lastRow > 1) {
             $itemBodyStyle = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
                'alignment' => ['vertical' => 'center'],
            ];
            
            // Terapkan style border ke semua data (mulai baris 2)
            $sheet->getStyle('A2:G' . $lastRow)->applyFromArray($itemBodyStyle);
            
            // Format kolom Harga (Kolom G) sebagai Rupiah
            $sheet->getStyle('G2:G' . $lastRow)
                  ->getNumberFormat()
                  ->setFormatCode('"Rp"#,##0;-"Rp"#,##0');
        }

        return [];
    }
}
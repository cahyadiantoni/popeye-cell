<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithDrawings;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Carbon\Carbon;

class SingleFakturSheet implements FromArray, WithTitle, WithHeadings, WithStyles, WithDrawings
{
    protected $faktur;
    protected $title;

    public function __construct($faktur, $title)
    {
        $this->faktur = $faktur;
        $this->title = $title;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function headings(): array
    {
        return [
            ['Nomor Faktur', $this->faktur->nomor_faktur],
            ['Pembeli', $this->faktur->pembeli],
            ['Tanggal Penjualan', Carbon::parse($this->faktur->tgl_jual)->format('d-m-Y')],
            ['Petugas', $this->faktur->petugas],
            ['Grade', $this->faktur->grade],
            ['Keterangan', $this->faktur->keterangan],
            [], // baris kosong untuk spasi
            ['No', 'Lok Spk', 'Merk Tipe', 'Harga', 'Sub Total'], // Header tabel barang
        ];
    }

    public function array(): array
    {
        $rows = [];

        // Menambahkan baris data barang
        foreach ($this->faktur->transaksiJuals as $index => $t) {
            $rows[] = [
                $index + 1,
                $t->lok_spk,
                $t->barang->tipe ?? '-',
                $t->harga,
                $t->subtotal,
            ];
        }

        // Menambahkan baris kosong sebelum rincian total
        $rows[] = [];

        // Menambahkan rincian subtotal, potongan, dan diskon jika ada
        if ($this->faktur->potongan_kondisi > 0 || $this->faktur->diskon > 0) {
            $rows[] = ['', '', '', 'Subtotal', $this->faktur->totalHarga];
        }

        if ($this->faktur->potongan_kondisi > 0) {
            $rows[] = ['', '', '', 'Potongan Kondisi', -$this->faktur->potongan_kondisi];
        }
        
        if ($this->faktur->diskon > 0) {
            $hargaSetelahPotongan = $this->faktur->totalHarga - $this->faktur->potongan_kondisi;
            $diskonAmount = ($hargaSetelahPotongan * $this->faktur->diskon) / 100;
            $rows[] = ['', '', '', 'Diskon (' . $this->faktur->diskon . '%)', -$diskonAmount];
        }

        // Menambahkan baris Total Akhir yang sudah benar dari $faktur->total
        $rows[] = ['Total Harga Keseluruhan', '', '', '', $this->faktur->total];

        // Menambahkan bagian Bukti Transfer jika ada
        if (!$this->faktur->bukti->isEmpty()) {
            $rows[] = [''];
            $rows[] = [''];
            $rows[] = ['--- Bukti Transfer ---'];
            foreach ($this->faktur->bukti as $index => $bukti) {
                $rows[] = [
                    'Bukti ' . ($index + 1),
                    'Keterangan: ' . ($bukti->keterangan ?? '-'),
                    'Nominal: Rp' . number_format($bukti->nominal, 0, ',', '.'),
                ];
            }
        }

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        // Style Header Info Faktur (latar biru)
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 12],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '2F75B5']],
            'borders' => ['outline' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
        ];
        for ($i = 1; $i <= 6; $i++) {
            $sheet->getStyle('A'.$i.':B'.$i)->applyFromArray($headerStyle)->getAlignment()->setVertical('center');
        }

        // Style Header Tabel Barang (latar hijau)
        $itemHeaderStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 12],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '70AD47']],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
            'alignment' => ['vertical' => 'center'],
        ];
        $sheet->getStyle('A8:E8')->applyFromArray($itemHeaderStyle);
        $sheet->getRowDimension(8)->setRowHeight(25);

        // Style Badan Tabel Barang
        $itemBodyStyle = [
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
            'alignment' => ['vertical' => 'center'],
        ];
        $itemCount = count($this->faktur->transaksiJuals);
        if ($itemCount > 0) {
            $sheet->getStyle('A9:E'.(9 + $itemCount - 1))->applyFromArray($itemBodyStyle);
        }
        
        // --- LOGIKA DINAMIS UNTUK STYLE TOTAL ---
        
        $startOfTotalSection = 9 + $itemCount + 1; // Baris setelah item + spasi kosong
        $finalTotalRow = $startOfTotalSection;
        if ($this->faktur->potongan_kondisi > 0 || $this->faktur->diskon > 0) $finalTotalRow++;
        if ($this->faktur->potongan_kondisi > 0) $finalTotalRow++;
        if ($this->faktur->diskon > 0) $finalTotalRow++;

        // Format angka sebagai mata uang Rupiah dari item hingga total akhir
        $sheet->getStyle('D9:E'.$finalTotalRow)->getNumberFormat()->setFormatCode('"Rp"#,##0;-"Rp"#,##0');
        // Rata kanan untuk kolom angka di bagian total
        $sheet->getStyle('E'.$startOfTotalSection.':E'.$finalTotalRow)->getAlignment()->setHorizontal('right');
        // Rata kanan untuk label di bagian total
        $sheet->getStyle('D'.$startOfTotalSection.':D'.$finalTotalRow)->getAlignment()->setHorizontal('right');

        // Style untuk baris Total Akhir (latar abu-abu)
        $sheet->getStyle('A'.$finalTotalRow.':E'.$finalTotalRow)->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '000000']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9D9D9']],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
            'alignment' => ['vertical' => 'center'],
        ]);
        
        // Menggabungkan sel untuk label total
        $sheet->mergeCells('A'.$finalTotalRow.':D'.$finalTotalRow);
        $sheet->getStyle('A'.$finalTotalRow)->getAlignment()->setHorizontal('center');
        $sheet->getRowDimension($finalTotalRow)->setRowHeight(25);

        // --- Penyesuaian Style Bukti Transfer ---
        if (!$this->faktur->bukti->isEmpty()) {
            $proofHeaderRow = $finalTotalRow + 3;
            $sheet->getStyle('A'.$proofHeaderRow.':C'.$proofHeaderRow)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 12],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C00000']],
                'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
            ]);
            $sheet->mergeCells('A'.$proofHeaderRow.':C'.$proofHeaderRow);
            $sheet->getRowDimension($proofHeaderRow)->setRowHeight(25);

            $proofDetailsStyle = [
                'font' => ['bold' => true],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8CBAD']],
                'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
                'alignment' => ['vertical' => 'center', 'wrapText' => true],
            ];
            $proofStartRow = $proofHeaderRow + 1;
            foreach ($this->faktur->bukti as $index => $bukti) {
                $sheet->getStyle('A'.$proofStartRow.':C'.$proofStartRow)->applyFromArray($proofDetailsStyle);
                $proofStartRow += 3;
            }
        }
        
        // Auto-size semua kolom
        foreach (range('A', 'E') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    public function drawings()
    {
        $drawings = [];
        
        if (!$this->faktur->bukti->isEmpty()) {
            // Kalkulasi posisi baris awal untuk gambar bukti
            $itemCount = count($this->faktur->transaksiJuals);
            $startOfTotalSection = 9 + $itemCount;
            $finalTotalRow = $startOfTotalSection;
            if ($this->faktur->potongan_kondisi > 0 || $this->faktur->diskon > 0) $finalTotalRow++;
            if ($this->faktur->potongan_kondisi > 0) $finalTotalRow++;
            if ($this->faktur->diskon > 0) $finalTotalRow++;
            
            $startRowForDrawing = $finalTotalRow + 5; // Posisi mulai untuk gambar pertama

            foreach ($this->faktur->bukti as $index => $bukti) {
                if (file_exists(storage_path('app/public/' . $bukti->foto))) {
                    $drawing = new Drawing();
                    $drawing->setName('Bukti ' . ($index + 1));
                    $drawing->setDescription('Bukti Transfer');
                    $drawing->setPath(storage_path('app/public/' . $bukti->foto));
                    $drawing->setHeight(500);
                    // Posisi gambar disesuaikan dengan loop
                    $drawing->setCoordinates('B' . ($startRowForDrawing + ($index * 4)));
                    $drawing->setOffsetX(10);
                    $drawing->setOffsetY(10);
                    $drawings[] = $drawing;
                }
            }
        }
        
        return $drawings;
    }
}
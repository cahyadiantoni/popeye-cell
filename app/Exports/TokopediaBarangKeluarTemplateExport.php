<?php

// app/Exports/TokopediaBarangKeluarTemplateExport.php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;

class TokopediaBarangKeluarTemplateExport implements FromArray, WithTitle
{
    public function array(): array
    {
        return [
            // Header (total_harga tidak ada di fitur ini)
            ['tgl_keluar','kode_toko','nama_am','nama_toko','nama_barang','quantity','alasan'],
            // Contoh isi:
            ['2025-10-01','12345','BUDI AM','TOKO ABC','KABEL DATA TYPE C','2','Pengiriman ke pelanggan'],
        ];
    }
    public function title(): string { return 'Template Toko BK'; }
}

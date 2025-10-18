<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;

class TokopediaBarangMasukTemplateExport implements FromArray, WithTitle
{
    public function array(): array
    {
        return [
            // Header:
            ['tgl_beli','nama_barang','quantity','harga_satuan','harga_ongkir','harga_potongan'],
            // Contoh isi (total_harga tidak diminta karena auto):
            ['2025-10-01','KABEL DATA TYPE C', '3', '45000', '10000', '5000'],
        ];
    }

    public function title(): string
    {
        return 'Template Tokopedia BM';
    }
}

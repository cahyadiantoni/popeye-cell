<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;

class HistoryTodoTransferTemplateExport implements FromArray, WithTitle
{
    public function array(): array
    {
        // Header + contoh pengisian pada baris ke-2
        return [
            ['tgl_transfer','kode_toko','nama_toko','nama_am','keterangan','nama_bank','norek_bank','nama_norek','nominal'],
            ['2025-10-01','12345','TOKO ABC','Budi AM','Pembayaran invoice 001','BCA','0123456789','PT ABC','1500000'],
        ];
    }

    public function title(): string
    {
        return 'Template Todo Transfer';
    }
}

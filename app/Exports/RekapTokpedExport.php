<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class RekapTokpedExport implements WithMultipleSheets
{
    protected $data;

    public function __construct($filtered)
    {
        $this->data = $filtered;
    }

    public function sheets(): array
    {
        $grouped = collect($this->data)->groupBy('prefix');
        $sheets = [];

        foreach ($grouped as $prefix => $records) {
            $sheets[] = new \App\Exports\RekapPerTokoSheet($records, $prefix);
        }

        return $sheets;
    }
}


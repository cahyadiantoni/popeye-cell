<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class FakturBawahExport implements WithMultipleSheets
{
    protected $fakturs;

    public function __construct($fakturs)
    {
        $this->fakturs = $fakturs;
    }

    public function sheets(): array
    {
        $sheets = [];

        foreach ($this->fakturs as $index => $faktur) {
            $sheetTitle = 'Faktur ' . $faktur->nomor_faktur;
            $sheets[] = new SingleFakturBawahSheet($faktur, $sheetTitle);
        }

        return $sheets;
    }
}

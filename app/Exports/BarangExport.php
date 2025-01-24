<?php

namespace App\Exports;

use App\Models\Barang;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use App\Models\Gudang;
use Illuminate\Support\Facades\Auth;

class BarangExport implements FromCollection, WithHeadings
{
    /**
     * Return collection of data to be exported.
     */

    protected $id;

    // Constructor to accept the id
    public function __construct($id)
    {
        $this->id = $id;
    }

    public function collection()
    {
        return Barang::with('gudang')->where('gudang_id', $this->id)->where('status_barang', 1)->get()->map(function ($barang) {
            return [
                'lok_spk' => $barang->lok_spk,
                'jenis' => $barang->jenis,
                'tipe' => $barang->tipe,
                'grade' => $barang->grade,
                'gudang' => $barang->gudang->nama_gudang ?? 'N/A',
            ];
        });
    }

    /**
     * Define headings for the Excel file.
     */
    public function headings(): array
    {
        return ['LOK_SPK', 'Jenis', 'Tipe', 'Grade', 'Gudang'];
    }
}


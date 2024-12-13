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
    public function collection()
    {
        // Mendapatkan auth id pengguna yang sedang login
        $authId = Auth::id();

        $gudangs = Gudang::where('pj_gudang', $authId)->select('id', 'nama_gudang')->get();

        // Mengambil id dari setiap gudang yang sesuai dengan auth_id
        $gudangIds = $gudangs->pluck('id');

        return Barang::with('gudang')->where('gudang_id', $gudangIds)->where('status_barang', 1)->get()->map(function ($barang) {
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


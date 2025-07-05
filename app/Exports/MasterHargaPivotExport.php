<?php

namespace App\Exports;

use App\Models\MasterHarga;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MasterHargaPivotExport implements WithMultipleSheets
{
    /**
     * Membuat file Excel dengan banyak sheet berdasarkan grade.
     */
    public function sheets(): array
    {
        $sheets = [];

        // 1. Ambil semua data dan tanggal header
        $semuaHarga = MasterHarga::all();
        $tanggalHeaders = $semuaHarga->pluck('tanggal')->unique()->sortBy('timestamp');

        // 2. Kelompokkan data berdasarkan grade (case-insensitive)
        $dataPerGrade = $semuaHarga->groupBy(function($item) {
            return strtoupper(trim($item->grade)); // Kunci grup adalah grade dalam huruf besar
        });

        // 3. Buat satu sheet untuk setiap grade
        foreach ($dataPerGrade as $grade => $dataGrade) {
            // Olah data menjadi format pivot untuk grade ini saja
            $dataPivot = $dataGrade
                ->groupBy(fn($item) => $item->tipe . '|' . $item->grade)
                ->map(function($group) {
                    $hargaPerTanggal = $group->keyBy(fn($item) => $item->tanggal->format('Y-m-d'))
                                            ->map(fn($item) => $item->harga);
                    return (object)[
                        'tipe'  => $group->first()->tipe,
                        'grade' => $group->first()->grade,
                        'harga_per_tanggal' => $hargaPerTanggal,
                    ];
                })
                ->sortBy('tipe')->values();
            
            // Tambahkan sheet baru ke dalam array
            $sheets[] = new HargaPerGradeSheetExport($grade, $dataPivot, $tanggalHeaders);
        }

        return $sheets;
    }
}
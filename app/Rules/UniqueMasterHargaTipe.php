<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Models\MasterHarga;

class UniqueMasterHargaTipe implements Rule
{
    protected $grade;

    public function __construct($grade)
    {
        $this->grade = $grade;
    }

    public function passes($attribute, $value)
    {
        // 1. Normalkan nilai 'tipe' dan 'grade' dari input form
        $normalizedTipe = MasterHarga::normalizeString($value);
        $normalizedGrade = MasterHarga::normalizeString($this->grade);

        // 2. Cari semua record yang memiliki 'tipe' yang sama (setelah dinormalkan).
        // Query ini cepat karena menggunakan index pada 'tipe_normalisasi'.
        $recordsWithSameTipe = MasterHarga::where('tipe_normalisasi', $normalizedTipe)->get();

        // 3. Jika tidak ada satupun tipe yang cocok, pasti unik. Loloskan.
        if ($recordsWithSameTipe->isEmpty()) {
            return true;
        }

        // 4. Loop melalui hasil yang sedikit itu untuk mengecek grade di level PHP.
        foreach ($recordsWithSameTipe as $record) {
            // Normalkan grade dari database
            $normalizedDbGrade = MasterHarga::normalizeString($record->grade);

            // Jika ada grade yang cocok, berarti kombinasinya sudah ada. Gagal.
            if ($normalizedDbGrade === $normalizedGrade) {
                return false;
            }
        }

        // 5. Jika loop selesai dan tidak ada grade yang cocok, berarti unik. Loloskan.
        return true;
    }

    public function message()
    {
        return 'Kombinasi Tipe dan Grade ini sudah ada.';
    }
}
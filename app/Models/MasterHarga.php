<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterHarga extends Model
{
    use HasFactory;

    /**
     * Nama tabel database yang terhubung dengan model ini.
     * Perlu didefinisikan karena nama tabel kita tidak mengikuti konvensi Laravel (master_hargas).
     *
     * @var string
     */
    protected $table = 't_master_harga';

    /**
     * Daftar kolom yang diizinkan untuk diisi secara massal (mass assignment).
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tipe',
        'grade',
        'harga',
        'tanggal',
    ];

    /**
     * The attributes that should be cast.
     * Casting memastikan data memiliki tipe yang benar saat diakses dari model.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'harga' => 'double', // Pastikan 'harga' selalu bertipe double/float
        'tanggal' => 'date',   // Pastikan 'tanggal' selalu menjadi objek Carbon (objek tanggal)
    ];

    protected static function booted()
    {
        // Setiap kali model akan disimpan (creating atau updating)
        static::saving(function ($masterHarga) {
            // Panggil helper kita untuk menormalkan 'tipe' dan isi kolom baru
            $masterHarga->tipe_normalisasi = self::normalizeString($masterHarga->tipe);
        });
    }

    public static function normalizeString($value)
    {
        // 1. Ubah ke huruf kecil
        $lower = strtolower($value);

        // 2. Hapus semua karakter selain huruf, angka, dan spasi
        $alphanumeric = preg_replace('/[^a-z0-9\s]/', '', $lower);

        // 3. Pecah menjadi kata-kata
        $words = explode(' ', $alphanumeric);

        // 4. Hapus kata-kata yang kosong (akibat spasi ganda) dan duplikat
        $uniqueWords = array_unique(array_filter($words));

        // 5. Urutkan kata-kata berdasarkan abjad (agar "Redmi Note" sama dengan "Note Redmi")
        sort($uniqueWords);

        // 6. Gabungkan kembali menjadi satu string tanpa spasi
        return implode('', $uniqueWords);
    }
}
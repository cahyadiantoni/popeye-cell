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
}
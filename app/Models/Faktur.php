<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Faktur extends Model
{
    use HasFactory;

    // Tentukan tabel yang digunakan
    protected $table = 't_faktur';

    // Tentukan kolom yang bisa diisi (mass assignable)
    protected $fillable = [
        'nomor_faktur',
        'pembeli',
        'tgl_jual',
        'petugas',
        'total',
    ];
}

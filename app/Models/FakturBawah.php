<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FakturBawah extends Model
{
    use HasFactory;

    // Tentukan tabel yang digunakan
    protected $table = 't_faktur_bawah';

    // Tentukan kolom yang bisa diisi (mass assignable)
    protected $fillable = [
        'nomor_faktur',
        'pembeli',
        'grade',
        'tgl_jual',
        'petugas',
        'total',
        'keterangan',
        'is_finish',
    ];

    public function barangs()
    {
        return $this->hasMany(TransaksiJualBawah::class, 'nomor_faktur', 'nomor_faktur');
    }
}

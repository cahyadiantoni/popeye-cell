<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FakturOutlet extends Model
{
    use HasFactory;

    // Tentukan tabel yang digunakan
    protected $table = 't_faktur_outlet';

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
        return $this->hasMany(TransaksiJualOutlet::class, 'nomor_faktur', 'nomor_faktur');
    }

    public function bukti()
    {
        return $this->hasMany(FakturBuktiOutlet::class, 't_faktur_id');
    }

}

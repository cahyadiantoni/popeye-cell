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
        'potongan_kondisi',
        'diskon',
        'grade',
        'keterangan',
        'is_finish',
    ];

    public function barangs()
    {
        return $this->hasMany(TransaksiJual::class, 'nomor_faktur', 'nomor_faktur');
    }

    public function bukti()
    {
        return $this->hasMany(FakturBukti::class, 't_faktur_id');
    }

    public function transaksiJuals()
    {
        return $this->hasMany(TransaksiJual::class, 'nomor_faktur', 'nomor_faktur');
    }

    public function payments()
    {
        return $this->hasMany(FakturPayment::class, 't_faktur_id');
    }

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FakturOnline extends Model
{
    use HasFactory;

    // Tentukan tabel yang digunakan
    protected $table = 't_faktur_online';

    // Tentukan kolom yang bisa diisi (mass assignable)
    protected $fillable = [
        'title',
        'toko',
        'tgl_jual',
        'petugas',
        'total',
        'keterangan',
        'grade',
        'is_finish',
    ];

    public function barangs()
    {
        return $this->hasMany(TransaksiJualOnline::class, 'faktur_online_id', 'id');
    }

    public function transaksiJuals()
    {
        return $this->hasMany(TransaksiJualOnline::class, 'faktur_online_id', 'id');
    }
}

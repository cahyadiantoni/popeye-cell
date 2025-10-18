<?php

// app/Models/TokopediaBarangKeluar.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TokopediaBarangKeluar extends Model
{
    use HasFactory;

    protected $table = 'tokopedia_barang_keluars';

    protected $fillable = [
        'tgl_keluar',
        'kode_toko',
        'nama_am',
        'nama_toko',
        'nama_barang',
        'quantity',
        'alasan',
    ];

    protected $casts = [
        'tgl_keluar' => 'date',
        'quantity'   => 'integer',
    ];
}

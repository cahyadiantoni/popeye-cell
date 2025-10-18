<?php

// app/Models/TokopediaBarangMasuk.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TokopediaBarangMasuk extends Model
{
    use HasFactory;

    protected $table = 'tokopedia_barang_masuks';

    protected $fillable = [
        'tgl_beli',
        'nama_barang',
        'quantity',
        'harga_satuan',
        'harga_ongkir',
        'harga_potongan',
        'total_harga',
    ];

    protected $casts = [
        'tgl_beli' => 'date',
        'quantity' => 'integer',
        'harga_satuan' => 'double',
        'harga_ongkir' => 'double',
        'harga_potongan' => 'double',
        'total_harga' => 'double',
    ];
}

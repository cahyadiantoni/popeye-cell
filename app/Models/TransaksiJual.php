<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransaksiJual extends Model
{
    use HasFactory;

    protected $table = 't_jual'; // Nama tabel
    protected $fillable = ['lok_spk', 'harga', 'nomor_faktur']; // Kolom yang dapat diisi

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'lok_spk', 'lok_spk');
    }
}


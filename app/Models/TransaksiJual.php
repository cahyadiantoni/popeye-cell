<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransaksiJual extends Model
{
    use HasFactory;

    protected $table = 't_jual'; // Nama tabel
    protected $fillable = ['lok_spk', 'harga', 'harga_acc', 'nomor_faktur']; // Kolom yang dapat diisi

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'lok_spk', 'lok_spk');
    }

    public function faktur()
    {
        return $this->belongsTo(Faktur::class, 'nomor_faktur', 'nomor_faktur');
    }

    public function setLokSpkAttribute($value)
    {
        // Hapus spasi di awal & akhir, spasi di tengah tetap
        $this->attributes['lok_spk'] = is_string($value) ? trim($value) : $value;
    }
}


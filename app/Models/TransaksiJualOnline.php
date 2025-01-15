<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransaksiJualOnline extends Model
{
    use HasFactory;

    protected $table = 't_jual_online'; // Nama tabel
    protected $fillable = ['lok_spk', 'invoice', 'harga', 'pj', 'faktur_online_id']; // Kolom yang dapat diisi

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'lok_spk', 'lok_spk');
    }
}


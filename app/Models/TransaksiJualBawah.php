<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransaksiJualBawah extends Model
{
    use HasFactory;

    protected $table = 't_jual_bawah';
    protected $fillable = ['lok_spk', 'harga', 'harga_acc', 'nomor_faktur'];

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'lok_spk', 'lok_spk');
    }
}


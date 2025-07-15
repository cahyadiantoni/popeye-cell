<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransaksiJualOutlet extends Model
{
    use HasFactory;

    protected $table = 't_jual_outlet';
    protected $fillable = ['lok_spk', 'harga', 'harga_acc', 'nomor_faktur'];

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'lok_spk', 'lok_spk');
    }

    public function faktur()
    {
        return $this->belongsTo(FakturOutlet::class, 'nomor_faktur', 'nomor_faktur');
    }
}


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

    public function setLokSpkAttribute($value)
    {
        // Hapus spasi di awal & akhir, spasi di tengah tetap
        $this->attributes['lok_spk'] = is_string($value) ? trim($value) : $value;
    }
}


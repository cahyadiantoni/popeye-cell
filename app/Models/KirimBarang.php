<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KirimBarang extends Model
{
    use HasFactory;

    protected $table = 't_kirim_barang';

    protected $fillable = ['lok_spk', 'kirim_id'];

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'lok_spk', 'lok_spk');
    }
}

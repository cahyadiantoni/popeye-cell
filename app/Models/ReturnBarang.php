<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReturnBarang extends Model
{
    use HasFactory;

    // Tentukan nama tabel jika berbeda dari default
    protected $table = 't_return_barang';

    // Kolom yang dapat diisi (mass assignable)
    protected $fillable = ['lok_spk', 't_return_id', 'harga',  'pedagang', 'alasan'];

    // Definisikan relasi ke model User
    public function barang()
    {
        return $this->belongsTo(Barang::class, 'lok_spk', 'lok_spk');
    }

    public function return()
    {
        return $this->belongsTo(ReturnModel::class, 't_return_id', 'id');
    }

    public function returnModel()
    {
        return $this->belongsTo(ReturnModel::class, 't_return_id', 'id');
    }

    public function setLokSpkAttribute($value)
    {
        // Hapus spasi di awal & akhir, spasi di tengah tetap
        $this->attributes['lok_spk'] = is_string($value) ? trim($value) : $value;
    }
}

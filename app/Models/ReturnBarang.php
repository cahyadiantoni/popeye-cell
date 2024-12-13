<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReturnBarang extends Model
{
    use HasFactory;

    // Tentukan nama tabel jika berbeda dari default
    protected $table = 't_return';

    // Kolom yang dapat diisi (mass assignable)
    protected $fillable = ['lok_spk', 'tgl_return', 'user_id'];

    // Definisikan relasi ke model User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function barang()
    {
        return $this->hasOne(Barang::class, 'lok_spk', 'lok_spk');
    }
}

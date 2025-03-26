<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReturnModel extends Model
{
    use HasFactory;

    // Tentukan nama tabel jika berbeda dari default
    protected $table = 't_return';

    // Kolom yang dapat diisi (mass assignable)
    protected $fillable = ['nomor_return', 'tgl_return', 'user_id', 'keterangan'];

    // Definisikan relasi ke model User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function returnBarang()
    {
        return $this->hasMany(ReturnBarang::class, 't_return_id', 'id');
    }
}

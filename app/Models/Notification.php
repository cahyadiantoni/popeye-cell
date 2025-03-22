<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    // Tentukan tabel yang digunakan
    protected $table = 'notifications';

    // Tentukan kolom yang bisa diisi (mass assignable)
    protected $fillable = [
        'pengirim_id',
        'penerima_id',
        'title',
        'isi',
        'link',
        'status',
    ];

    public function pengirim()
    {
        return $this->belongsTo(User::class, 'pengirim_id', 'id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'penerima_id', 'id');
    }

}

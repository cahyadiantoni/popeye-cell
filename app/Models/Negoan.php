<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Negoan extends Model
{
    use HasFactory;

    // Tentukan tabel yang digunakan
    protected $table = 't_negoan';

    // Tentukan kolom yang bisa diisi (mass assignable)
    protected $fillable = [
        'tipe',
        'is_manual',
        'harga_asal',
        'harga_nego',
        'note_nego',
        'harga_acc',
        'note_acc',
        'status',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

}

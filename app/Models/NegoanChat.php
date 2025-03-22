<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NegoanChat extends Model
{
    use HasFactory;

    // Tentukan tabel yang digunakan
    protected $table = 't_negoan_chat';

    // Tentukan kolom yang bisa diisi (mass assignable)
    protected $fillable = [
        't_negoan_id',
        'user_id',
        'isi',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function negoan()
    {
        return $this->belongsTo(Negoan::class, 't_negoan_id', 'id');
    }
}

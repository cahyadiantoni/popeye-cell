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
        'user_id',
        'title',
        'isi',
        'link',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

}

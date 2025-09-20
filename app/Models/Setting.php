<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terhubung dengan model.
     */
    protected $table = 't_settings';

    /**
     * Atribut yang dapat diisi secara massal.
     */
    protected $fillable = [
        'name',
        'value',
        'keterangan',
        'is_active',
    ];

    /**
     * Tipe data cast untuk atribut.
     * Ini penting agar 'is_active' selalu dikenali sebagai boolean (true/false)
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];
}
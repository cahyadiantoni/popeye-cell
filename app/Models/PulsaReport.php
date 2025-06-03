<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PulsaReport extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'pulsa_report';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'Tanggal',
        'Keterangan',
        'Cabang',
        'Jumlah',
        'Jenis',
        'Saldo',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'Tanggal' => 'date',
        'Jumlah' => 'decimal:2', // Casting untuk memastikan format desimal
        'Saldo' => 'decimal:2',  // Casting untuk memastikan format desimal
    ];
}
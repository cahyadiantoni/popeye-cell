<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TokpedInputDeposit extends Model
{
    use HasFactory;

    protected $table = 'tokped_input_deposit';

    protected $fillable = [
        'tgl_penarikan',
        'dana_dalam_pengawasan',
        'saldo_akhir',
        'periode',
    ];
}


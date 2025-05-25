<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TokpedDataDeposit extends Model
{
    use HasFactory;

    protected $table = 'tokped_data_deposit';

    protected $fillable = [
        'date',
        'mutation',
        'description',
        'description_short',
        'invoice_full',
        'invoice_end',
        'nominal',
        'balance',
    ];
}

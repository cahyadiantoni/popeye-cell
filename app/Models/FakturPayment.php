<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FakturPayment extends Model
{
    use HasFactory;
    protected $table = 't_payments';
    protected $fillable = [
        'order_id',
        'channel',
        'status',
        't_faktur_id',
        'nomor_faktur',
        'amount',
        'snap_token',
        'payment_gateway',
        'xendit_invoice_id',
        'invoice_url',
    ];

    public function faktur()
    {
        return $this->belongsTo(Faktur::class, 't_faktur_id');
    }
}

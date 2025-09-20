<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FakturPayment extends Model
{
    use HasFactory;
    protected $table = 't_payments';
    protected $fillable = [
        'order_id',
        'channel',
        'status',
        'nomor_faktur',
        'amount',
        'snap_token',
        'payment_gateway',
        'xendit_invoice_id',
        'invoice_url',
        'paymentable_id',
        'paymentable_type',
    ];

    public function paymentable(): MorphTo
    {
        return $this->morphTo();
    }
}

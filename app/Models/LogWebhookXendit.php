<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogWebhookXendit extends Model
{
    protected $table = 'log_webhook_xendit';

    protected $fillable = [
        'event_id',
        'external_id',
        'payment_id',
        'status',
        'amount',
        'currency',
        'payment_method',
        'payment_channel',
        'bank_code',
        'payment_destination',
        'created_at_xendit',
        'updated_at_xendit',
        'paid_at_xendit',
        'success_redirect_url',
        'failure_redirect_url',
        'raw_body',
        'headers',
        'source_ip',
        'handled_result',
        'processing_note',
    ];

    protected $casts = [
        'raw_body' => 'array',
        'headers'  => 'array',
    ];
}

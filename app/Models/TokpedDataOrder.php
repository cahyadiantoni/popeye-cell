<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // Tambahkan ini

class TokpedDataOrder extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tokped_data_orders';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tokped_input_order_id',
        'invoice_number',
        'payment_at',
        'latest_status',
        'completed_at',
        'cancelled_at',
        'product_name',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'payment_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /**
     * Get the input order that owns the data order.
     */
    public function tokpedInputOrder(): BelongsTo // Definisikan return type
    {
        return $this->belongsTo(TokpedInputOrder::class, 'tokped_input_order_id');
    }
}
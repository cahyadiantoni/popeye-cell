<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TokpedInputOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama_toko',
        'periode_laporan',
        'tanggal_penarikan_data',
    ];

    protected $casts = [
        'tanggal_penarikan_data' => 'datetime',
    ];

    /**
     * Get all of the dataOrders for the TokpedInputOrder
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function dataOrders(): HasMany
    {
        return $this->hasMany(TokpedDataOrder::class);
    }
}
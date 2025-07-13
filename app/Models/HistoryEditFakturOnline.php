<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistoryEditFakturOnline extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terhubung dengan model ini.
     */
    protected $table = 't_history_edit_faktur_online';

    /**
     * Kolom-kolom yang dapat diisi secara massal.
     */
    protected $fillable = [
        'faktur_id',
        'update',
        'user_id',
    ];

    /**
     * Mendefinisikan relasi "belongsTo" ke model FakturOnline.
     */
    public function faktur(): BelongsTo
    {
        return $this->belongsTo(FakturOnline::class, 'faktur_id', 'id');
    }

    /**
     * Mendefinisikan relasi "belongsTo" ke model User.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
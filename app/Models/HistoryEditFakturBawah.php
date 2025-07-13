<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistoryEditFakturBawah extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terhubung dengan model ini.
     *
     * @var string
     */
    protected $table = 't_history_edit_faktur_bawah';

    /**
     * Kolom-kolom yang dapat diisi secara massal (mass assignable).
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'faktur_id',
        'update',
        'user_id',
    ];

    /**
     * Mendefinisikan relasi "belongsTo" ke model FakturBawah.
     * Satu catatan riwayat dimiliki oleh satu faktur bawah.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function faktur(): BelongsTo
    {
        // Perubahan di baris ini
        return $this->belongsTo(FakturBawah::class, 'faktur_id', 'id');
    }

    /**
     * Mendefinisikan relasi "belongsTo" ke model User.
     * Satu catatan riwayat dibuat oleh satu user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}   
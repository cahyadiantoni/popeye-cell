<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistoryEditFakturOutlet extends Model
{
    use HasFactory;
    protected $table = 't_history_edit_faktur_outlet';
    protected $fillable = ['faktur_id', 'update', 'user_id'];

    public function faktur(): BelongsTo {
        return $this->belongsTo(FakturOutlet::class, 'faktur_id', 'id');
    }
    public function user(): BelongsTo {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
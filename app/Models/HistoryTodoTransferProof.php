<?php

// app/Models/HistoryTodoTransferProof.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoryTodoTransferProof extends Model
{
    use HasFactory;

    protected $fillable = [
        'history_todo_transfer_id',
        'path',
        'original_name',
        'mime_type',
        'size',
        'uploaded_by',
    ];

    public function todo()
    {
        return $this->belongsTo(HistoryTodoTransfer::class, 'history_todo_transfer_id');
    }
}

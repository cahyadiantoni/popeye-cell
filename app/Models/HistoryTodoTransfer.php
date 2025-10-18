<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoryTodoTransfer extends Model
{
    use HasFactory;

    protected $table = 'history_todo_transfers';

    protected $fillable = [
        'tgl_transfer',
        'kode_toko',
        'nama_toko',
        'nama_am',
        'keterangan',
        'nama_bank',
        'norek_bank',
        'nama_norek',
        'nominal',
    ];

    protected $casts = [
        'tgl_transfer' => 'date',
        'nominal' => 'double',
    ];
}

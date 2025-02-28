<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdmTodoTf extends Model
{
    use HasFactory;

    protected $table = 'adm_todo_tf';
    protected $fillable = [
        'tgl',
        'kode_lok',
        'nama_toko',
        'user_id',
        'keterangan',
        'bank',
        'no_rek',
        'nama_rek',
        'nominal',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bukti()
    {
        return $this->hasMany(AdmTodoTfBukti::class, 'adm_todo_tf_id');
    }
}

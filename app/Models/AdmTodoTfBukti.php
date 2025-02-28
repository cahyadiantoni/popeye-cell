<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdmTodoTfBukti extends Model
{
    use HasFactory;

    protected $table = 'adm_todo_tf_bukti';
    protected $fillable = [
        'adm_todo_tf_id',
        'keterangan',
        'foto',
    ];

    public function admTodoTf()
    {
        return $this->belongsTo(AdmTodoTf::class, 'adm_todo_tf_id');
    }
}

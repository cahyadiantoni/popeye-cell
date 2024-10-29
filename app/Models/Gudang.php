<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gudang extends Model
{
    use HasFactory;

    protected $table = 't_gudang';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'nama_gudang',
        'pj_gudang',
    ];

    // Relasi ke User
    public function penanggungJawab()
    {
        return $this->belongsTo(User::class, 'pj_gudang');
    }
}


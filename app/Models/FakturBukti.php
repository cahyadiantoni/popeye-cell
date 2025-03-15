<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FakturBukti extends Model
{
    use HasFactory;

    protected $table = 't_faktur_bukti';
    protected $fillable = [
        't_faktur_id',
        'keterangan',
        'nominal',
        'foto',
    ];

    public function faktur()
    {
        return $this->belongsTo(Faktur::class, 't_faktur_id');
    }
}

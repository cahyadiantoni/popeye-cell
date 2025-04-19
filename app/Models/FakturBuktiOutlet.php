<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FakturBuktiOutlet extends Model
{
    use HasFactory;

    protected $table = 't_faktur_bukti_outlet';
    protected $fillable = [
        't_faktur_id',
        'keterangan',
        'nominal',
        'foto',
    ];

    public function faktur()
    {
        return $this->belongsTo(FakturOutlet::class, 't_faktur_id');
    }
}

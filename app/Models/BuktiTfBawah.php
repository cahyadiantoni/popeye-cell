<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BuktiTfBawah extends Model
{
    use HasFactory;

    protected $table = 't_bukti_tf_bawah';
    protected $fillable = [
        'kesimpulan_id',
        'keterangan',
        'nominal',
        'foto',
    ];

    public function kesimpulan()
    {
        return $this->belongsTo(KesimpulanBawah::class, 'kesimpulan_id');
    }
}

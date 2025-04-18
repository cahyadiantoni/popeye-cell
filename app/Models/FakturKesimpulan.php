<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FakturKesimpulan extends Model
{
    use HasFactory;

    // Tentukan tabel yang digunakan
    protected $table = 't_faktur_kesimpulan_bawah';

    // Tentukan kolom yang bisa diisi (mass assignable)
    protected $fillable = [
        'kesimpulan_id',
        'faktur_id',
    ];

    public function kesimpulan()
    {
        return $this->belongsTo(KesimpulanBawah::class, 'kesimpulan_id');
    }

    public function faktur()
    {
        return $this->belongsTo(FakturBawah::class, 'faktur_id');
    }
}

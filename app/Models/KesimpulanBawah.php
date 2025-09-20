<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class KesimpulanBawah extends Model
{
    use HasFactory;

    // Tentukan tabel yang digunakan
    protected $table = 't_kesimpulan_bawah';

    // Tentukan kolom yang bisa diisi (mass assignable)
    protected $fillable = [
        'nomor_kesimpulan',
        'tgl_jual',
        'total',
        'grand_total',
        'potongan_kondisi',
        'diskon',
        'keterangan',
        'is_lunas',
    ];

    public function bukti()
    {
        return $this->hasMany(BuktiTfBawah::class, 'kesimpulan_id');
    }

    // KesimpulanBawah.php
    public function fakturKesimpulans()
    {
        return $this->hasMany(FakturKesimpulan::class, 'kesimpulan_id');
    }

    public function getTotalBarangAttribute()
    {
        return $this->fakturKesimpulans
            ->map(function ($fakturKesimpulans) {
                return optional($fakturKesimpulans->faktur)->barangs->count();
            })
            ->sum();
    }    

    public function getTotalNominalAttribute()
    {
        return $this->bukti->sum('nominal');
    }

    public function getPembeliAttribute()
    {
        $firstFakturKesimpulan = $this->fakturKesimpulans->first();

        if ($firstFakturKesimpulan && $firstFakturKesimpulan->faktur) {
            return $firstFakturKesimpulan->faktur->pembeli;
        }
        
        return '-';
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(FakturPayment::class, 'paymentable');
    }

}

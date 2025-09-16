<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CekSOBarang extends Model
{
    use HasFactory;
    protected $table = 't_cek_so_barang';
    protected $fillable = ['t_cek_so_id', 'lok_spk', 'status', 'petugas_scan', 'lokasi'];
    public function cekSO()
    {
        return $this->belongsTo(CekSO::class, 't_cek_so_id');
    }

    public function setLokSpkAttribute($value)
    {
        // Hapus spasi di awal & akhir, spasi di tengah tetap
        $this->attributes['lok_spk'] = is_string($value) ? trim($value) : $value;
    }
}

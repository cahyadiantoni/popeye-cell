<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengambilanAm extends Model
{
    use HasFactory;

    protected $table = 'pengambilan_am';

    protected $fillable = [
        'tgl_ambil',
        'lok_spk',
        'nama_am',
        'kode_toko',
        'nama_toko',
        'user_id',
        'keterangan',
    ];

    /**
     * Relasi ke model Barang
     */
    public function barang()
    {
        return $this->belongsTo(Barang::class, 'lok_spk', 'lok_spk');
    }

    /**
     * Relasi ke model User
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

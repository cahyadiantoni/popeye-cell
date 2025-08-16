<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventaris extends Model
{
    use HasFactory;

    protected $table = 'inventaris';

    /**
     * Atribut yang dapat diisi secara massal.
     */
    protected $fillable = [
        'id',
        'gudang_id', // BARU
        'tgl',
        'nama',
        'kode_toko',
        'nama_toko',
        'lok_spk',
        'jenis',
        'tipe',
        'kelengkapan',
        'keterangan',
        'status', // BARU
        'tgl_gantian', // BARU
        'alasan_gantian', // BARU
    ];

    /**
     * Definisikan relasi ke model Gudang.
     * Pastikan Anda memiliki model Gudang yang terhubung ke tabel 't_gudang'.
     */
    public function gudang()
    {
        // Sesuaikan 'App\Models\Gudang' jika namespace atau nama model Anda berbeda
        return $this->belongsTo(Gudang::class, 'gudang_id');
    }
}
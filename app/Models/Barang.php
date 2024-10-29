<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Barang extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 't_barang';
    protected $primaryKey = 'lok_spk';
    public $incrementing = false;
    protected $keyType = 'string';
    

    /**
     * The attributes that are not mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'lok_spk', 'jenis', 'merek', 'tipe', 'imei', 'kelengkapan',
        'kerusakan', 'grade', 'qt_bunga', 'harga_jual', 'harga_beli',
        'keterangan1', 'keterangan2', 'keterangan3', 'nama_petugas',
        'dt_beli', 'dt_lelang', 'dt_jatuh_tempo', 'dt_input', 'user_id','gudang_id'
    ];
}

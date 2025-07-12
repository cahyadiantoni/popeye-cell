<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Barang extends Model
{
    use HasFactory;

    protected $table = 't_barang';
    protected $primaryKey = 'lok_spk';
    public $incrementing = false;
    protected $keyType = 'string';
    
    protected $fillable = [
        'lok_spk', 'jenis', 'merek', 'tipe', 'imei', 'kelengkapan',
        'kerusakan', 'grade', 'qt_bunga', 'harga_jual', 'harga_beli',
        'keterangan1', 'keterangan2', 'keterangan3', 'nama_petugas',
        'dt_beli', 'dt_lelang', 'dt_jatuh_tempo', 'dt_input', 'user_id','gudang_id','no_faktur','status_barang'
    ];

    protected static function booted()
    {
        static::saving(function ($barang) {
            $barang->tipe_normalisasi = self::normalizeString($barang->tipe);
        });
    }

    public static function normalizeString($value)
    {
        if (is_null($value)) return null;
        
        $lower = strtolower($value);
        $alphanumeric = preg_replace('/[^a-z0-9\s]/', '', $lower);
        $words = explode(' ', $alphanumeric);
        $uniqueWords = array_unique(array_filter($words));
        sort($uniqueWords);
        return implode('', $uniqueWords);
    }

    public function gudang()
    {
        return $this->belongsTo(Gudang::class, 'gudang_id');
    }

    public function faktur()
    {
        return $this->belongsTo(Faktur::class, 'no_faktur', 'nomor_faktur');
    }

    public function barangs()
    {
        return $this->hasMany(Barang::class, 'no_faktur', 'nomor_faktur');
    }

    public function fakturBawah()
    {
        return $this->belongsTo(FakturBawah::class, 'no_faktur', 'nomor_faktur');
    }

    public function fakturOutlet()
    {
        return $this->belongsTo(FakturOutlet::class, 'no_faktur', 'nomor_faktur');
    }
}
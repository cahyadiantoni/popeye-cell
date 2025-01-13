<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kirim extends Model
{
    use HasFactory;

    protected $table = 't_kirim';

    protected $fillable = [
        'pengirim_gudang_id',
        'penerima_gudang_id',
        'pengirim_user_id',
        'penerima_user_id',
        'status',
        'dt_kirim',
        'dt_terima',
    ];

    // Di dalam Model Kirim
    public function pengirimGudang()
    {
        return $this->belongsTo(Gudang::class, 'pengirim_gudang_id');
    }

    public function penerimaGudang()
    {
        return $this->belongsTo(Gudang::class, 'penerima_gudang_id');
    }

    public function pengirimUser()
    {
        return $this->belongsTo(User::class, 'pengirim_user_id');
    }

    public function penerimaUser()
    {
        return $this->belongsTo(User::class, 'penerima_user_id');
    }

    // public function barang()
    // {
    //     return $this->belongsTo(Barang::class, 'lok_spk', 'lok_spk');
    // }
}

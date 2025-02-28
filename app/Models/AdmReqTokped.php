<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdmReqTokped extends Model
{
    use HasFactory;

    protected $table = 'adm_req_tokped';
    protected $fillable = ['tgl', 'user_id', 'kode_lok', 'nama_toko', 'alasan', 'status'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bukti()
    {
        return $this->hasMany(AdmReqTokpedBukti::class);
    }

    public function items()
    {
        return $this->hasMany(AdmReqTokpedItem::class);
    }
}


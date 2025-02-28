<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdmReqTokpedItem extends Model
{
    use HasFactory;

    protected $table = 'adm_req_tokped_item';
    protected $fillable = ['adm_req_tokped_id', 'adm_item_tokped_id', 'nama_barang', 'quantity'];

    public function request()
    {
        return $this->belongsTo(AdmReqTokped::class, 'adm_req_tokped_id');
    }

    public function item()
    {
        return $this->belongsTo(AdmItemTokped::class, 'adm_item_tokped_id');
    }
}


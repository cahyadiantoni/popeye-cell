<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdmItemTokped extends Model
{
    use HasFactory;

    protected $table = 'adm_item_tokped';
    protected $fillable = ['name', 'keterangan'];

    public function requestItems()
    {
        return $this->hasMany(AdmReqTokpedItem::class);
    }
}


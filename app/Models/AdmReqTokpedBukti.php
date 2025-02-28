<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdmReqTokpedBukti extends Model
{
    use HasFactory;

    protected $table = 'adm_req_tokped_bukti';
    protected $fillable = ['adm_req_tokped_id', 'keterangan', 'foto'];

    public function request()
    {
        return $this->belongsTo(AdmReqTokped::class, 'adm_req_tokped_id');
    }
}

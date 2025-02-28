<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdmSetting extends Model
{
    use HasFactory;

    protected $table = 'adm_setting';
    protected $fillable = [
        'kode',
        'name',
        'is_active',
        'keterangan'
    ];
}

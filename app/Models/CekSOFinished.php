<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CekSOFinished extends Model
{
    use HasFactory;
    protected $table = 't_cek_so_finished';
    protected $fillable = ['t_cek_so_id', 'lok_spk', 'status', 'petugas_scan', 'lokasi'];
    public function cekSO()
    {
        return $this->belongsTo(CekSO::class, 't_cek_so_id');
    }
}

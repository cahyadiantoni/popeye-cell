<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CekSO extends Model
{
    use HasFactory;
    protected $table = 't_cek_so';
    protected $fillable = ['kode', 'petugas', 'gudang_id', 'jumlah_scan_sistem', 'jumlah_input_manual', 'jumlah_upload_excel', 'jumlah_stok', 'waktu_mulai', 'waktu_selesai', 'hasil', 'catatan', 'is_finished'];

    // Relasi ke Gudang
    public function gudang()
    {
        return $this->belongsTo(Gudang::class, 'gudang_id');
    }
}

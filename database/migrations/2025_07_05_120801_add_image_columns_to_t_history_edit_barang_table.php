<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('t_history_edit_barang', function (Blueprint $table) {
            // Tambahkan 3 kolom baru setelah kolom 'user_id'
            // Kolom ini akan menyimpan path/lokasi file gambar
            $table->string('foto_barang')->nullable()->after('user_id');
            $table->string('foto_imei')->nullable()->after('foto_barang');
            $table->string('foto_device_cek')->nullable()->after('foto_imei');
        });
    }

    public function down()
    {
        Schema::table('t_history_edit_barang', function (Blueprint $table) {
            // Logika untuk rollback/membatalkan migrasi
            $table->dropColumn(['foto_barang', 'foto_imei', 'foto_device_cek']);
        });
    }
};
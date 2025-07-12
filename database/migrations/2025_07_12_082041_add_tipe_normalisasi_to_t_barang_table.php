<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('t_barang', function (Blueprint $table) {
            // Tambahkan kolom baru setelah kolom 'tipe' untuk kerapian.
            // Index ditambahkan agar pencarian di kolom ini menjadi sangat cepat.
            $table->string('tipe_normalisasi')->after('tipe')->nullable()->index();
        });
    }

    public function down()
    {
        Schema::table('t_barang', function (Blueprint $table) {
            // Logika untuk membatalkan migrasi
            $table->dropColumn('tipe_normalisasi');
        });
    }
};
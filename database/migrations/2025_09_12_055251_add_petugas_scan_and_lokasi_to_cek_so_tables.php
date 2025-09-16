<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Menambahkan kolom ke tabel transaksi (yang sedang berjalan)
        Schema::table('t_cek_so_barang', function (Blueprint $table) {
            $table->string('petugas_scan')->nullable()->after('status');
            $table->string('lokasi')->nullable()->after('petugas_scan');
        });

        // Menambahkan kolom ke tabel hasil akhir (arsip)
        Schema::table('t_cek_so_finished', function (Blueprint $table) {
            $table->string('petugas_scan')->nullable()->after('status');
            $table->string('lokasi')->nullable()->after('petugas_scan');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('t_cek_so_barang', function (Blueprint $table) {
            $table->dropColumn(['petugas_scan', 'lokasi']);
        });

        Schema::table('t_cek_so_finished', function (Blueprint $table) {
            $table->dropColumn(['petugas_scan', 'lokasi']);
        });
    }
};
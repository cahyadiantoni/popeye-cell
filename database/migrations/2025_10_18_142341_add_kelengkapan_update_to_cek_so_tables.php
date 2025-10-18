<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        // t_cek_so_barang
        Schema::table('t_cek_so_barang', function (Blueprint $table) {
            // enum nullable: wajib dipilih di UI, tapi data lama boleh null
            $table->enum('kelengkapan_update', ['BOX','BTG','OTHER'])->nullable()->after('lokasi');
        });

        // t_cek_so_finished
        Schema::table('t_cek_so_finished', function (Blueprint $table) {
            $table->enum('kelengkapan_update', ['BOX','BTG','OTHER'])->nullable()->after('lokasi');
        });
    }

    public function down(): void
    {
        Schema::table('t_cek_so_barang', function (Blueprint $table) {
            $table->dropColumn('kelengkapan_update');
        });

        Schema::table('t_cek_so_finished', function (Blueprint $table) {
            $table->dropColumn('kelengkapan_update');
        });
    }
};

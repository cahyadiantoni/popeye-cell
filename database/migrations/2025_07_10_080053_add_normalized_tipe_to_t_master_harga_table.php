<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('t_master_harga', function (Blueprint $table) {
            // Tambahkan kolom baru untuk menyimpan versi 'bersih' dari tipe.
            // Index ditambahkan untuk membuat pencarian super cepat.
            $table->string('tipe_normalisasi')->after('tipe')->nullable()->index();
        });
    }

    public function down()
    {
        Schema::table('t_master_harga', function (Blueprint $table) {
            $table->dropColumn('tipe_normalisasi');
        });
    }
};

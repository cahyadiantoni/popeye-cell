<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('t_barang', function (Blueprint $table) {
            $table->string('lok_spk', 255)->primary();
            $table->string('jenis', 255);
            $table->string('merek', 255);
            $table->string('tipe', 255);
            $table->string('imei', 255);
            $table->string('kelengkapan', 255);
            $table->string('kerusakan', 255);
            $table->string('grade', 255);
            $table->string('gudang_id', 255);
            $table->integer('status_barang')->length(10);
            $table->string('qt_bunga', 255);
            $table->double('harga_jual', 15, 2);
            $table->double('harga_beli', 15, 2);
            $table->string('keterangan1', 255)->nullable();
            $table->string('keterangan2', 255)->nullable();
            $table->string('keterangan3', 255)->nullable();
            $table->string('nama_petugas', 255);
            $table->datetime('dt_input');
            $table->datetime('dt_beli')->nullable();
            $table->datetime('dt_lelang')->nullable();
            $table->datetime('dt_jatuh_tempo')->nullable();
            $table->string('user_id', 255);
            $table->timestamps(); // automatically adds 'created_at' and 'updated_at'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_barang');
    }
};

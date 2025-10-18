<?php

// database/migrations/2025_10_18_000002_create_tokopedia_barang_keluars_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('tokopedia_barang_keluars', function (Blueprint $table) {
            $table->id();
            $table->date('tgl_keluar');
            $table->string('kode_toko');        // only digits
            $table->string('nama_am')->nullable();
            $table->string('nama_toko')->nullable();
            $table->string('nama_barang');      // UPPERCASE, trimmed (no leading/trailing spaces)
            $table->integer('quantity');
            $table->text('alasan')->nullable();
            $table->timestamps();

            $table->unique(['tgl_keluar','kode_toko','nama_barang','quantity'], 'uniq_tbk_comp');
            $table->index(['tgl_keluar','kode_toko']);
            $table->index(['nama_barang']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('tokopedia_barang_keluars');
    }
};

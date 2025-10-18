<?php

// database/migrations/2025_10_18_000001_create_tokopedia_barang_masuks_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('tokopedia_barang_masuks', function (Blueprint $table) {
            $table->id();
            $table->date('tgl_beli');
            $table->string('nama_barang'); // UPPERCASE, no leading/trailing spaces
            $table->integer('quantity');
            $table->double('harga_satuan');
            $table->double('harga_ongkir')->default(0);
            $table->double('harga_potongan')->default(0);
            $table->double('total_harga'); // dihitung otomatis
            $table->timestamps();

            $table->unique(['tgl_beli', 'nama_barang', 'quantity', 'total_harga'], 'uniq_tbm_comp');
            $table->index(['tgl_beli', 'nama_barang']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('tokopedia_barang_masuks');
    }
};

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
        Schema::create('t_jual_bawah', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->string('lok_spk', 255); // Lokasi SPK
            $table->double('harga'); // Harga jual
            $table->double('harga_acc'); // Harga acc
            $table->string('nomor_faktur', 255); // Nomor faktur
            $table->timestamps(); // Created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_jual_bawah');
    }
};

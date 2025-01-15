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
        Schema::create('t_jual_online', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->string('lok_spk', 255); // Lokasi SPK
            $table->string('invoice', 255); // Invoice
            $table->double('harga'); // Harga jual
            $table->double('pj'); // PJ
            $table->unsignedBigInteger('faktur_online_id'); // Foreign key to t_faktur_online
            $table->foreign('faktur_online_id')->references('id')->on('t_faktur_online')->onDelete('cascade'); // Relasi dengan cascade delete
            $table->timestamps(); // Created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_jual_online');
    }
};

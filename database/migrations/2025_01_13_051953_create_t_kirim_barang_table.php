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
        Schema::create('t_kirim_barang', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->string('lok_spk'); // Lokasi SPK
            $table->unsignedBigInteger('kirim_id'); // Foreign key to t_kirim table
            $table->timestamps(); // Created at and Updated at columns

            // Optional: Define foreign key constraint (if `kirim_id` references another table)
            $table->foreign('kirim_id')->references('id')->on('t_kirim')->onDelete('cascade');
       
            // Foreign key to t_barang table
            $table->foreign('lok_spk')->references('lok_spk')->on('t_barang')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_kirim_barang');
    }
};

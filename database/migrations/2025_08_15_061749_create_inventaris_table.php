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
        Schema::create('inventaris', function (Blueprint $table) {
            $table->id();
            $table->date('tgl')->nullable();
            $table->string('nama')->nullable();
            $table->string('kode_toko')->nullable();
            $table->string('nama_toko')->nullable();
            $table->string('lok_spk')->nullable();
            // Diubah menjadi string untuk fleksibilitas
            $table->string('jenis', 50)->nullable(); 
            $table->string('tipe')->nullable();
            // Diubah menjadi string untuk fleksibilitas
            $table->string('kelengkapan', 50)->nullable(); 
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventaris');
    }
};
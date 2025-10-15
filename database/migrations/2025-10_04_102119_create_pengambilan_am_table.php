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
        Schema::create('pengambilan_am', function (Blueprint $table) {
            $table->id();
            $table->date('tgl_ambil');
            $table->string('lok_spk');
            $table->string('nama_am');
            $table->string('kode_toko')->nullable();
            $table->string('nama_toko')->nullable();
            $table->foreignId('user_id')->constrained('users');
            $table->text('keterangan')->nullable();
            $table->timestamps();

            // Definisi foreign key ke tabel t_barang
            $table->foreign('lok_spk')->references('lok_spk')->on('t_barang')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengambilan_am');
    }
};

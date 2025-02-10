<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('t_cek_so_barang', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('t_cek_so_id');
            $table->string('lok_spk');
            $table->foreign('t_cek_so_id')->references('id')->on('t_cek_so')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_cek_so_barang');
    }
};

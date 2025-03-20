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
        Schema::create('t_return_barang', function (Blueprint $table) {
            $table->id();
            $table->string('lok_spk');
            $table->unsignedBigInteger('t_return_id');
            $table->foreign('lok_spk', 255)->references('lok_spk')->on('t_barang')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('t_return_id')->references('id')->on('t_return')->onDelete('cascade');
            $table->double('harga');
            $table->text('alasan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_return_barang');
    }
};

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
        Schema::create('t_kesimpulan_bawah', function (Blueprint $table) {
            $table->id(); // Kolom id sebagai primary key auto increment
            $table->string('nomor_kesimpulan'); 
            $table->date('tgl_jual'); 
            $table->unsignedBigInteger('total');
            $table->unsignedBigInteger('grand_total');
            $table->unsignedBigInteger('potongan_kondisi')->default(0);
            $table->integer('diskon')->default(0);
            $table->text('keterangan')->nullable(); 
            $table->integer('is_lunas')->default(0);
            $table->integer('is_finish')->default(0);
            $table->timestamps(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_kesimpulan_bawah');
    }
};

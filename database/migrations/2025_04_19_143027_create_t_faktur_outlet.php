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
        Schema::create('t_faktur_outlet', function (Blueprint $table) {
            $table->id(); 
            $table->string('nomor_faktur');
            $table->string('pembeli'); 
            $table->text('grade'); 
            $table->date('tgl_jual');
            $table->string('petugas');
            $table->unsignedBigInteger('total');
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
        Schema::dropIfExists('t_faktur_outlet');
    }
};

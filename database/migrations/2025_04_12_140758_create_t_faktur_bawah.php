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
        Schema::create('t_faktur_bawah', function (Blueprint $table) {
            $table->id(); // Kolom id sebagai primary key auto increment
            $table->string('nomor_faktur'); // Kolom nomor_faktur dengan tipe varchar
            $table->string('pembeli'); // Kolom pembeli dengan tipe varchar
            $table->text('grade'); // Kolom pembeli dengan tipe varchar
            $table->date('tgl_jual'); // Kolom tgl_jual dengan tipe date
            $table->string('petugas'); // Kolom petugas dengan tipe varchar
            $table->unsignedBigInteger('total'); // Kolom total dengan tipe angka (bigint tanpa tanda)
            $table->text('keterangan')->nullable(); // Kolom petugas dengan tipe varchar
            $table->integer('is_finish')->default(0);
            $table->timestamps(); // Menambahkan kolom created_at dan updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_faktur_bawah');
    }
};

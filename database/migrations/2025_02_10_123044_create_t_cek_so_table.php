<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('t_cek_so', function (Blueprint $table) {
            $table->id();
            $table->string('kode');
            $table->string('petugas');
            $table->unsignedBigInteger('gudang_id');
            $table->integer('jumlah_scan')->nullable();
            $table->integer('jumlah_stok')->nullable();
            $table->dateTime('waktu_mulai');
            $table->dateTime('waktu_selesai')->nullable();
            $table->integer('hasil')->default(0);
            $table->text('catatan')->nullable();
            $table->integer('is_finished')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_cek_so');
    }
};

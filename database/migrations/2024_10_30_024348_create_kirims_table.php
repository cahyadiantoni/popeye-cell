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
        Schema::create('t_kirim', function (Blueprint $table) {
            $table->id();
            $table->string('pengirim_gudang_id', 255);
            $table->string('penerima_gudang_id', 255);
            $table->string('pengirim_user_id', 255);
            $table->string('penerima_user_id', 255);
            $table->integer('status')->length(10);
            $table->datetime('dt_kirim')->nullable();
            $table->datetime('dt_terima')->nullable();
            $table->timestamps(); // Jika ingin mencatat waktu dibuat dan diubah
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_kirim');
    }
};

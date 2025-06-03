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
        Schema::create('pulsa_master', function (Blueprint $table) {
            $table->string('kode')->primary(); // 'kode' sebagai primary key
            $table->string('nama_toko')->nullable();
            $table->string('pasca_bayar1')->nullable();
            $table->string('pasca_bayar2')->nullable();
            $table->string('token1')->nullable();
            $table->string('token2')->nullable();
            $table->string('pam1')->nullable();
            $table->string('pam2')->nullable();
            $table->string('pulsa1')->nullable();
            $table->string('pulsa2')->nullable();
            $table->string('pulsa3')->nullable();
            $table->timestamps(); // Kolom created_at dan updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pulsa_master');
    }
};
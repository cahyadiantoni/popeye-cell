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
        Schema::create('tokped_input_orders', function (Blueprint $table) {
            $table->id();
            $table->string('nama_toko');
            $table->string('periode_laporan'); // Contoh: "01/05/2025 - 23/05/2025"
            $table->dateTime('tanggal_penarikan_data'); // Contoh: "23/05/2025 23:59"
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tokped_input_orders');
    }
};

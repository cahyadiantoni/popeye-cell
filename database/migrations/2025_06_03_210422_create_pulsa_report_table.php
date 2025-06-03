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
        Schema::create('pulsa_report', function (Blueprint $table) {
            $table->id(); // Primary key auto-increment default
            $table->date('Tanggal');
            $table->text('Keterangan')->nullable();
            $table->string('Cabang')->nullable();
            $table->decimal('Jumlah', 15, 2)->default(0); // Angka/uang, misal 15 digit total, 2 di belakang koma
            $table->string('Jenis')->nullable();
            $table->decimal('Saldo', 15, 2)->default(0); // Angka/uang
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pulsa_report');
    }
};
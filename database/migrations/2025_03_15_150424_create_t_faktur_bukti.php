<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('t_faktur_bukti', function (Blueprint $table) {
            $table->id();
            $table->foreignId('t_faktur_id')->constrained('t_faktur')->onDelete('cascade');
            $table->text('keterangan')->nullable();
            $table->double('nominal')->nullable();
            $table->text('foto')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_faktur_bukti');
    }
};
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
        Schema::create('t_jual_outlet', function (Blueprint $table) {
            $table->id();
            $table->string('lok_spk', 255);
            $table->double('harga');
            $table->double('harga_acc');
            $table->string('nomor_faktur', 255);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_jual_outlet');
    }
};

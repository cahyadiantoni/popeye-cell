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
        Schema::create('t_faktur_kesimpulan_bawah', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kesimpulan_id')->constrained('t_kesimpulan_bawah')->onDelete('cascade');
            $table->foreignId('faktur_id')->constrained('t_faktur_bawah')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_faktur_kesimpulan_bawah');
    }
};

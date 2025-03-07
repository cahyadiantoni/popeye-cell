<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('t_gudang', function (Blueprint $table) {
            $table->string('id', 255)->primary();
            $table->string('nama_gudang', 255);
            $table->string('pj_gudang', 255);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_gudang');
    }
};

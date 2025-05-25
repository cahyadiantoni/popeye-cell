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
        Schema::create('tokped_input_deposit', function (Blueprint $table) {
            $table->id();
            $table->dateTime('tgl_penarikan');
            $table->bigInteger('dana_dalam_pengawasan');
            $table->bigInteger('saldo_akhir');
            $table->string('periode'); // bisa juga dipisah jadi 'periode_awal' dan 'periode_akhir' jika ingin lebih terstruktur
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tokped_input_deposits');
    }
};

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
        Schema::create('t_payments', function (Blueprint $table) {
        $table->id();
        $table->string('order_id')->unique(); // ID transaksi Midtrans
        $table->string('channel')->nullable(); // metode: gopay, qris, dll
        $table->string('status')->default('pending');
        $table->unsignedBigInteger('t_faktur_id');
        $table->string('nomor_faktur');
        $table->integer('amount');
        $table->text('snap_token')->nullable();
        $table->timestamps();

        $table->foreign('t_faktur_id')->references('id')->on('t_faktur')->onDelete('cascade');
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_payments');
    }
};

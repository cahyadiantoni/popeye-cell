<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('t_payments', function (Blueprint $table) {
            // Kolom untuk membedakan gateway
            $table->string('payment_gateway')->default('midtrans')->after('id');
            
            // Kolom spesifik Xendit
            $table->string('xendit_invoice_id')->nullable()->after('snap_token');
            $table->text('invoice_url')->nullable()->after('xendit_invoice_id');
            
            // Buat snap_token bisa null karena tidak semua pembayaran punya ini
            $table->string('snap_token')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('t_payments', function (Blueprint $table) {
            $table->dropColumn(['payment_gateway', 'xendit_invoice_id', 'invoice_url']);
            $table->string('snap_token')->nullable(false)->change(); // Kembalikan seperti semula
        });
    }
};
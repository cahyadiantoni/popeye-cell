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
        Schema::table('t_payments', function (Blueprint $table) {
            // 1. Hapus foreign key dan kolom t_faktur_id yang lama.
            // Pastikan nama constraint 't_payments_t_faktur_id_foreign' sesuai dengan yang ada di database Anda.
            // Anda bisa memeriksanya atau menghapusnya secara manual jika nama berbeda.
            $table->dropForeign(['t_faktur_id']); // Uncomment jika ada foreign key constraint
            $table->dropColumn('t_faktur_id');

            // 2. Tambahkan kolom untuk polymorphic relationship.
            // Ini akan membuat kolom 'paymentable_id' (unsignedBigInteger) dan 'paymentable_type' (string).
            $table->morphs('paymentable');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('t_payments', function (Blueprint $table) {
            // 1. Hapus kolom polymorphic.
            $table->dropMorphs('paymentable');

            // 2. Kembalikan kolom t_faktur_id yang lama.
            $table->unsignedBigInteger('t_faktur_id')->nullable();
        });
    }
};

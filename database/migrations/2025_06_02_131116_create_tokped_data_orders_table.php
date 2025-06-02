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
        Schema::create('tokped_data_orders', function (Blueprint $table) {
            $table->id();
            // Foreign key untuk TokpedInputOrder
            $table->foreignId('tokped_input_order_id') // Membuat kolom UNSIGNED BIGINT
                  ->constrained('tokped_input_orders') // Menambahkan foreign key constraint ke tabel 'tokped_input_orders' pada kolom 'id'
                  ->onDelete('cascade'); // Jika TokpedInputOrder dihapus, maka TokpedDataOrder terkait juga akan dihapus

            $table->string('invoice_number');
            $table->dateTime('payment_at')->nullable(); // Diubah menjadi nullable agar sesuai dengan contoh controller store, di mana bisa jadi null
            $table->string('latest_status')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->text('product_name');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tokped_data_orders');
    }
};

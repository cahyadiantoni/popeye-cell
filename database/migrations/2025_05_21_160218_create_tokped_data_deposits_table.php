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
        Schema::create('tokped_data_deposit', function (Blueprint $table) {
            $table->id();
            $table->dateTime('date');
            $table->string('mutation'); // Debit / Credit
            $table->text('description');
            $table->text('description_short');
            $table->text('invoice_full');
            $table->text('invoice_end');
            $table->bigInteger('nominal');
            $table->bigInteger('balance');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tokped_data_deposits');
    }
};

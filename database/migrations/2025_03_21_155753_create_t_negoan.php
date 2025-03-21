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
        Schema::create('t_negoan', function (Blueprint $table) {
            $table->id();
            $table->string('tipe', 255);
            $table->double('harga_nego'); 
            $table->text('note_nego')->nullable();
            $table->double('harga_acc'); 
            $table->text('note_acc')->nullable();
            $table->integer('status')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_negoan');
    }
};

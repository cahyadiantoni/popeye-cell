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
        Schema::create('t_negoan_chat', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('t_negoan_id');
            $table->unsignedBigInteger('user_id');
            $table->foreign('t_negoan_id')->references('id')->on('t_negoan')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->text('isi');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_negoan_chat');
    }
};

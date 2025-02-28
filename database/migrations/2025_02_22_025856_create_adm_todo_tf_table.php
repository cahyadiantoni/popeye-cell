<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('adm_todo_tf', function (Blueprint $table) {
            $table->id();
            $table->date('tgl');
            $table->string('kode_lok');
            $table->string('nama_toko');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->text('keterangan')->nullable();
            $table->string('bank');
            $table->string('no_rek');
            $table->string('nama_rek');
            $table->double('nominal');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adm_todo_tf');
    }
};

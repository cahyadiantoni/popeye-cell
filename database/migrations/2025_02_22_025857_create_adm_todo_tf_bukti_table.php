<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('adm_todo_tf_bukti', function (Blueprint $table) {
            $table->id();
            $table->foreignId('adm_todo_tf_id')->constrained('adm_todo_tf')->onDelete('cascade');
            $table->text('keterangan')->nullable();
            $table->text('foto')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adm_todo_tf_bukti');
    }
};

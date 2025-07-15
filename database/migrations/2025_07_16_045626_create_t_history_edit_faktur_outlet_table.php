<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('t_history_edit_faktur_outlet', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('faktur_id'); // Tanpa foreign key
            $table->text('update');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('t_history_edit_faktur_outlet');
    }
};
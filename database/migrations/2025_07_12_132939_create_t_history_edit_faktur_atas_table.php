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
        // Membuat tabel t_history_edit_faktur_atas
        Schema::create('t_history_edit_faktur_atas', function (Blueprint $table) {
            $table->id();

            // Foreign key ke tabel t_faktur. Jika faktur dihapus, history juga ikut terhapus.
            $table->foreignId('faktur_id')->constrained('t_faktur')->onDelete('cascade');
            
            // Kolom untuk menyimpan detail perubahan
            $table->text('update');

            // Foreign key ke tabel users. Jika user dihapus, nilainya menjadi NULL.
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_history_edit_faktur_atas');
    }
};
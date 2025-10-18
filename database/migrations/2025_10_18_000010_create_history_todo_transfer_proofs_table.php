<?php

// database/migrations/2025_10_18_000010_create_history_todo_transfer_proofs_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('history_todo_transfer_proofs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('history_todo_transfer_id')
                  ->constrained('history_todo_transfers') // nama tabel fitur sebelumnya
                  ->cascadeOnDelete();
            $table->string('path');          // path di storage
            $table->string('original_name'); // nama file asli
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size')->default(0);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['history_todo_transfer_id']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('history_todo_transfer_proofs');
    }
};

<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('history_todo_transfers', function (Blueprint $table) {
            $table->id();
            $table->date('tgl_transfer');
            $table->string('kode_toko');
            $table->string('nama_toko')->nullable();
            $table->string('nama_am')->nullable();
            $table->text('keterangan')->nullable();
            $table->string('nama_bank')->nullable();
            $table->string('norek_bank');
            $table->string('nama_norek')->nullable();
            $table->double('nominal');
            $table->timestamps();

            $table->unique(['tgl_transfer', 'kode_toko', 'norek_bank', 'nominal'], 'uniq_ttf_comp');
            $table->index(['tgl_transfer', 'kode_toko']);
            $table->index(['nama_bank']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('history_todo_transfers');
    }
};

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
        Schema::table('inventaris', function (Blueprint $table) {
            // Menambahkan foreign key ke tabel t_gudang
            $table->bigInteger('gudang_id')->nullable();
            $table->foreign('gudang_id')->references('id')->on('t_gudang')->onDelete('set null');
            
            // Menambahkan kolom status dengan nilai default 1 (Aktif)
            $table->tinyInteger('status')->default(1)->after('keterangan');

            // Menambahkan kolom untuk fitur "Gantian"
            $table->date('tgl_gantian')->nullable()->after('status');
            $table->text('alasan_gantian')->nullable()->after('tgl_gantian');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventaris', function (Blueprint $table) {
            // Hapus foreign key constraint sebelum drop kolom
            $table->dropForeign(['gudang_id']);
            
            $table->dropColumn(['gudang_id', 'status', 'tgl_gantian', 'alasan_gantian']);
        });
    }
};
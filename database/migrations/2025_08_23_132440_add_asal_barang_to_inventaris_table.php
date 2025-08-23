<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Pastikan Anda sudah menginstal package doctrine/dbal
        // jalankan: composer require doctrine/dbal
        
        Schema::table('inventaris', function (Blueprint $table) {
            // 1. Tambahkan kolom baru 'asal_barang' setelah 'gudang_id'
            $table->string('asal_barang')->nullable()->after('gudang_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('inventaris', function (Blueprint $table) {
            // Hapus kolom 'asal_barang' jika migrasi di-rollback
            $table->dropColumn('asal_barang');

        });
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('t_cek_so', function (Blueprint $table) {
            // Tambahkan kolom baru yang lebih spesifik
            $table->integer('jumlah_scan_sistem')->default(0)->after('petugas');
            $table->integer('jumlah_input_manual')->default(0)->after('jumlah_scan_sistem');
            $table->integer('jumlah_upload_excel')->default(0)->after('jumlah_input_manual');

            // Hapus kolom lama
            $table->dropColumn('jumlah_scan');
            $table->dropColumn('jumlah_manual');
        });
    }

    public function down()
    {
        Schema::table('t_cek_so', function (Blueprint $table) {
            // Kembalikan kolom lama jika rollback
            $table->integer('jumlah_scan')->default(0);
            $table->integer('jumlah_manual')->default(0);

            // Hapus kolom baru
            $table->dropColumn('jumlah_scan_sistem');
            $table->dropColumn('jumlah_input_manual');
            $table->dropColumn('jumlah_upload_excel');
        });
    }
};
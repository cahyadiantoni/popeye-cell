<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('t_master_harga', function (Blueprint $table) {
            // Tambahkan kolom 'grade' setelah kolom 'tipe'
            $table->string('grade')->after('tipe');
        });
    }

    public function down()
    {
        Schema::table('t_master_harga', function (Blueprint $table) {
            $table->dropColumn('grade');
        });
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('t_return_barang', function (Blueprint $table) {
            $table->string('pedagang')->after('alasan');
        });
    }

    public function down(): void
    {
        Schema::table('t_return_barang', function (Blueprint $table) {
            $table->dropColumn('pedagang');
        });
    }
};

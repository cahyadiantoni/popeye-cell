<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('t_faktur', function (Blueprint $table) {
            $table->integer('gudang_id')->default(0)->after('keterangan');
        });

        Schema::table('t_faktur_online', function (Blueprint $table) {
            $table->integer('gudang_id')->default(0)->after('keterangan');
        });
    }

    public function down(): void
    {
        Schema::table('t_faktur', function (Blueprint $table) {
            $table->dropColumn('gudang_id');
        });

        Schema::table('t_faktur_online', function (Blueprint $table) {
            $table->dropColumn('gudang_id');
        });
    }
};

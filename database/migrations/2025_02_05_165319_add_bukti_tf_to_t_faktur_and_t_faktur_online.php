<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('t_faktur', function (Blueprint $table) {
            $table->text('bukti_tf')->nullable()->after('keterangan');
        });

        Schema::table('t_faktur_online', function (Blueprint $table) {
            $table->text('bukti_tf')->nullable()->after('keterangan');
        });
    }

    public function down(): void
    {
        Schema::table('t_faktur', function (Blueprint $table) {
            $table->dropColumn('bukti_tf');
        });

        Schema::table('t_faktur_online', function (Blueprint $table) {
            $table->dropColumn('bukti_tf');
        });
    }
};

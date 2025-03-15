<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('t_faktur', function (Blueprint $table) {
            $table->integer('is_lunas')->default(0)->after('bukti_tf');
        });
    }

    public function down(): void
    {
        Schema::table('t_faktur', function (Blueprint $table) {
            $table->dropColumn('is_lunas');
        });
    }
};

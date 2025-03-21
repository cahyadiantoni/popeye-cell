<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('t_jual', function (Blueprint $table) {
            $table->double('harga_acc')->default(0)->after('harga');
        });
    }

    public function down(): void
    {
        Schema::table('t_jual', function (Blueprint $table) {
            $table->dropColumn('harga_acc');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('adm_setting', function (Blueprint $table) {
            $table->string('kode')->after('id'); // Menambahkan kolom kode setelah nominal
        });
    }

    public function down(): void
    {
        Schema::table('adm_setting', function (Blueprint $table) {
            $table->dropColumn('kode');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('adm_req_tokped', function (Blueprint $table) {
            $table->integer('status')->default(0)->after('alasan'); // Menambahkan kolom status setelah nominal
        });
    }

    public function down(): void
    {
        Schema::table('adm_req_tokped', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};

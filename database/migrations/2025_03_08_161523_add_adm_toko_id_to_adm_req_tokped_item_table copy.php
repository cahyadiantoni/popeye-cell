<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('adm_req_tokped_item', function (Blueprint $table) {
            $table->string('adm_toko_id', 255)->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('adm_req_tokped_item', function (Blueprint $table) {
            $table->dropColumn('adm_toko_id');
        });
    }
};

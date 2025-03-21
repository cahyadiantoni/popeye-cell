<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('t_negoan', function (Blueprint $table) {
            $table->integer('is_manual')->default(0)->after('tipe');
            $table->double('harga_asal')->after('is_manual'); 
        });
    }

    public function down(): void
    {
        Schema::table('t_negoan', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });
    }
};

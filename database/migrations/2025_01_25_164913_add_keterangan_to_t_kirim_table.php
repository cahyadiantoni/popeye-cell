<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddKeteranganToTKirimTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('t_kirim', function (Blueprint $table) {
            $table->text('keterangan')->nullable()->after('status'); // Ganti 'kolom_sebelumnya' dengan nama kolom sebelum keterangan.
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('t_kirim', function (Blueprint $table) {
            $table->dropColumn('keterangan');
        });
    }
}


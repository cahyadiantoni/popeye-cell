<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNoFakturToTBarangTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('t_barang', function (Blueprint $table) {
            $table->string('no_faktur')->nullable()->after('user_id');;
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('t_barang', function (Blueprint $table) {
            $table->dropColumn('no_faktur');
        });
    }
}

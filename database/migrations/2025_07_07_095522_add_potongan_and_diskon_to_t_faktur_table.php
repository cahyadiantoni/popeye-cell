<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('t_faktur', function (Blueprint $table) {
            // Menambahkan kolom 'potongan_kondisi' sesuai permintaan Anda.
            // unsignedBigInteger adalah tipe data integer besar tanpa nilai negatif.
            // ->default(0) akan mengisi kolom dengan nilai 0 jika tidak ada nilai yang diberikan.
            $table->unsignedBigInteger('potongan_kondisi')->default(0)->after('total');

            // Menambahkan kolom 'diskon' setelah 'potongan_kondisi'.
            // integer adalah tipe data bilangan bulat standar.
            $table->integer('diskon')->default(0)->after('potongan_kondisi');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('t_faktur', function (Blueprint $table) {
            // Menghapus kolom jika migrasi di-rollback.
            // Nama kolom disesuaikan menjadi 'potongan_kondisi'.
            $table->dropColumn(['potongan_kondisi', 'diskon']);
        });
    }
};
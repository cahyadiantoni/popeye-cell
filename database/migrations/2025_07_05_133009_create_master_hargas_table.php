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
        // Membuat tabel dengan nama 't_master_harga'
        Schema::create('t_master_harga', function (Blueprint $table) {
            // Kolom ID sebagai primary key yang auto-increment
            $table->id();

            // Kolom tipe dengan tipe VARCHAR(255)
            $table->string('tipe');

            $table->double('harga');

            // Kolom tanggal dengan tipe DATE
            $table->date('tanggal');

            // Kolom created_at dan updated_at yang otomatis diisi oleh Laravel
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('t_master_harga');
    }
};
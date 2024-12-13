<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTJualTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('t_jual', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->string('lok_spk', 255); // Lokasi SPK
            $table->double('harga'); // Harga jual
            $table->string('nomor_faktur', 255); // Nomor faktur
            $table->timestamps(); // Created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('t_jual');
    }
}


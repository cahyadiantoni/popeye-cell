<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('adm_req_tokped', function (Blueprint $table) {
            $table->id();
            $table->date('tgl');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('kode_lok');
            $table->string('nama_toko');
            $table->text('alasan');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('adm_req_tokped');
    }
};

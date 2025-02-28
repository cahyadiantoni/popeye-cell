<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('adm_req_tokped_bukti', function (Blueprint $table) {
            $table->id();
            $table->foreignId('adm_req_tokped_id')->constrained('adm_req_tokped')->onDelete('cascade');
            $table->text('keterangan');
            $table->text('foto');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('adm_req_tokped_bukti');
    }
};


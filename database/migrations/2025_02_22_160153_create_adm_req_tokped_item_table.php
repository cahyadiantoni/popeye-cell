<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('adm_req_tokped_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('adm_req_tokped_id')->constrained('adm_req_tokped')->onDelete('cascade');
            $table->foreignId('adm_item_tokped_id')->constrained('adm_item_tokped')->onDelete('cascade');
            $table->string('nama_barang')->nullable();
            $table->integer('quantity');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('adm_req_tokped_item');
    }
};


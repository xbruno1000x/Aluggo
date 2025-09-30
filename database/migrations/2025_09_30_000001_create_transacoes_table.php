<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('transacoes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->decimal('valor_venda', 15, 2);
            $table->date('data_venda');
            $table->unsignedBigInteger('imovel_id');
            $table->timestamps();

            $table->foreign('imovel_id')->references('id')->on('imoveis')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('transacoes');
    }
};

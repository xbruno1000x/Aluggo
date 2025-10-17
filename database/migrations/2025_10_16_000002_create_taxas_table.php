<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Em ambientes de desenvolvimento/tests, permitir re-criar a tabela se existir
        Schema::dropIfExists('taxas');

        Schema::create('taxas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('imovel_id')->nullable();
            $table->unsignedBigInteger('aluguel_id')->nullable();
            $table->string('tipo', 50); // condominio, iptu, outros
            $table->decimal('valor', 12, 2);
            $table->date('data_pagamento');
            $table->enum('pagador', ['proprietario', 'locatario'])->default('proprietario');
            $table->text('observacao')->nullable();
            $table->timestamps();
            // FKs adicionados condicionalmente abaixo
        });

        if (Schema::hasTable('imoveis')) {
            Schema::table('taxas', function (Blueprint $table) {
                $table->foreign('imovel_id')->references('id')->on('imoveis')->onDelete('cascade');
            });
        }

        if (Schema::hasTable('alugueis')) {
            Schema::table('taxas', function (Blueprint $table) {
                $table->foreign('aluguel_id')->references('id')->on('alugueis')->onDelete('cascade');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('taxas');
    }
};

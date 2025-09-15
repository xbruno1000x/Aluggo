<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProprietariosTable extends Migration
{
    public function up()
    {
        Schema::create('proprietarios', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('cpf')->unique();
            $table->string('telefone')->nullable();
            $table->string('email')->unique();
            $table->string('password'); // Para armazenar a senha hash
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('proprietarios');
    }
}
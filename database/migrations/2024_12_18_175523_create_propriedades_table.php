<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('propriedades', function (Blueprint $table) {
            $table->id();
            $table->string('nome'); // Nome da propriedade
            $table->string('endereco'); // Endereço
            $table->text('descricao')->nullable(); // Descrição da propriedade
            $table->foreignId('proprietario_id')->constrained()->onDelete('cascade'); // Relacionada ao Proprietário
            $table->timestamps();
        });
    }    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('propriedades');
    }
};

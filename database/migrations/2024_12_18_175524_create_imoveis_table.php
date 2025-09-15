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
        Schema::create('imoveis', function (Blueprint $table) {
            $table->id();
            $table->string('nome'); // Nome do imóvel
            $table->enum('tipo', ['apartamento', 'terreno', 'loja']); // Tipo de imóvel
            $table->decimal('valor_compra', 15, 2)->nullable(); // Valor de compra (opcional)
            $table->enum('status', ['disponível', 'vendido', 'alugado']); // Status do imóvel
            $table->date('data_aquisicao')->nullable(); // Data de aquisição
            $table->foreignId('propriedade_id')->constrained()->onDelete('cascade'); // Relacionado a Propriedade
            $table->timestamps();
        });
    }    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('imoveis');
    }
};

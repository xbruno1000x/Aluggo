<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('obras', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('descricao');
            $table->decimal('valor', 12, 2)->default(0);
            $table->date('data_inicio')->nullable();
            $table->date('data_fim')->nullable();
            $table->unsignedBigInteger('imovel_id');
            $table->timestamps();
            // foreign key added conditionally below
        });

        // adicionar constraint somente se tabela referenciada existir
        if (Schema::hasTable('imoveis')) {
            Schema::table('obras', function (Blueprint $table) {
                $table->foreign('imovel_id')->references('id')->on('imoveis')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('obras');
    }
};

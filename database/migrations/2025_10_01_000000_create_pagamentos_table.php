<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pagamentos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('aluguel_id')->nullable(false);
            $table->date('referencia_mes'); // stored as first day of month
            $table->decimal('valor_devido', 12, 2);
            $table->decimal('valor_recebido', 12, 2)->default(0);
            $table->enum('status', ['pending','partial','paid','disputed'])->default('pending');
            $table->dateTime('data_pago')->nullable();
            $table->text('observacao')->nullable();
            $table->timestamps();

            $table->foreign('aluguel_id')->references('id')->on('alugueis')->onDelete('cascade');
            $table->unique(['aluguel_id', 'referencia_mes']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagamentos');
    }
};

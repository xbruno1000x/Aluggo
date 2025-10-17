<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('propriedades')) {
            // se a tabela ainda não existe (ordem de migrations), não tentar alterar
            return;
        }

        Schema::table('propriedades', function (Blueprint $table) {
            if (!Schema::hasColumn('propriedades', 'bairro')) {
                $table->string('bairro')->after('endereco')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('propriedades')) {
            return;
        }

        Schema::table('propriedades', function (Blueprint $table) {
            if (Schema::hasColumn('propriedades', 'bairro')) {
                $table->dropColumn('bairro');
            }
        });
    }
};
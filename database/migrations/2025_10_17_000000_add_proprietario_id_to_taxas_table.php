<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('taxas', function (Blueprint $table) {
            if (!Schema::hasColumn('taxas', 'proprietario_id')) {
                $table->unsignedBigInteger('proprietario_id')->nullable()->after('id');
            }
        });

        // adicionar foreign key somente se a tabela proprietarios já existir
        if (Schema::hasTable('proprietarios')) {
            Schema::table('taxas', function (Blueprint $table) {
                $table->foreign('proprietario_id')->references('id')->on('proprietarios')->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('taxas')) {
            Schema::table('taxas', function (Blueprint $table) {
                if (Schema::hasColumn('taxas', 'proprietario_id')) {
                    // tentar dropar FK se existir
                    try {
                        $table->dropForeign(['proprietario_id']);
                    } catch (\Throwable $e) {
                        // ignore
                    }
                    $table->dropColumn('proprietario_id');
                }
            });
        }
    }
};

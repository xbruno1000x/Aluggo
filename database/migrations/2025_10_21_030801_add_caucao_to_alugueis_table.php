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
        Schema::table('alugueis', function (Blueprint $table) {
            $table->decimal('caucao', 10, 2)->nullable()->after('valor_mensal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alugueis', function (Blueprint $table) {
            $table->dropColumn('caucao');
        });
    }
};

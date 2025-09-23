<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('propriedades', function (Blueprint $table) {
            $table->string('bairro')->after('endereco')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('propriedades', function (Blueprint $table) {
            $table->dropColumn('bairro');
        });
    }
};
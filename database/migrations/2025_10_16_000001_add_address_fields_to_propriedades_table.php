<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('propriedades', function (Blueprint $table) {
            $table->string('cep', 20)->nullable()->after('endereco');
            $table->string('cidade', 100)->nullable()->after('cep');
            $table->string('estado', 100)->nullable()->after('cidade');
        });
    }

    public function down()
    {
        Schema::table('propriedades', function (Blueprint $table) {
            $table->dropColumn(['cep', 'cidade', 'estado']);
        });
    }
};

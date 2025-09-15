<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTwoFactorSecretToProprietariosTable extends Migration
{
    public function up()
    {
        Schema::table('proprietarios', function (Blueprint $table) {
            $table->string('two_factor_secret')->nullable(); // Adiciona o campo two_factor_secret
        });
    }

    public function down()
    {
        Schema::table('proprietarios', function (Blueprint $table) {
            $table->dropColumn('two_factor_secret'); // Remove o campo
        });
    }
}

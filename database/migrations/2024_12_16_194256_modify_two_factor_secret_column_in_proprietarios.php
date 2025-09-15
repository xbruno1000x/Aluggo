<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyTwoFactorSecretColumnInProprietarios extends Migration
{
    public function up()
    {
        Schema::table('proprietarios', function (Blueprint $table) {
            $table->text('two_factor_secret')->nullable()->change();
        });
    }    

    public function down()
    {
        Schema::table('proprietarios', function (Blueprint $table) {
            $table->string('two_factor_secret', 255)->change(); // ou o tamanho que preferir
        });
    }
}

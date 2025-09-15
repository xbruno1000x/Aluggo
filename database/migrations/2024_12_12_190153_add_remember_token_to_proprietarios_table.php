<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('proprietarios', function (Blueprint $table) {
            $table->string('remember_token', 100)->nullable()->after('password');
        });
    }
    
    public function down()
    {
        Schema::table('proprietarios', function (Blueprint $table) {
            $table->dropColumn('remember_token');
        });
    }    
};

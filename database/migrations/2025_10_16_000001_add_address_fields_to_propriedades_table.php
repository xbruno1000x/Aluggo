<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('propriedades')) {
            return;
        }

        Schema::table('propriedades', function (Blueprint $table) {
            if (!Schema::hasColumn('propriedades', 'cep')) {
                $table->string('cep', 20)->nullable()->after('endereco');
            }
            if (!Schema::hasColumn('propriedades', 'cidade')) {
                $table->string('cidade', 100)->nullable()->after('cep');
            }
            if (!Schema::hasColumn('propriedades', 'estado')) {
                $table->string('estado', 100)->nullable()->after('cidade');
            }
        });
    }

    public function down()
    {
        if (!Schema::hasTable('propriedades')) {
            return;
        }

        Schema::table('propriedades', function (Blueprint $table) {
            $toDrop = [];
            if (Schema::hasColumn('propriedades', 'cep')) $toDrop[] = 'cep';
            if (Schema::hasColumn('propriedades', 'cidade')) $toDrop[] = 'cidade';
            if (Schema::hasColumn('propriedades', 'estado')) $toDrop[] = 'estado';
            if (!empty($toDrop)) {
                $table->dropColumn($toDrop);
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('taxas')) {
            return;
        }

        Schema::table('taxas', function (Blueprint $table) {
            if (! Schema::hasColumn('taxas', 'propriedade_id')) {
                $table->unsignedBigInteger('propriedade_id')->nullable()->after('imovel_id');
                $table->foreign('propriedade_id')->references('id')->on('propriedades')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (! Schema::hasTable('taxas')) {
            return;
        }

        Schema::table('taxas', function (Blueprint $table) {
            if (Schema::hasColumn('taxas', 'propriedade_id')) {
                $table->dropForeign(['propriedade_id']);
                $table->dropColumn('propriedade_id');
            }
        });
    }
};

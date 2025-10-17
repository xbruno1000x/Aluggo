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
        if (!Schema::hasTable('imoveis')) {
            return;
        }

        Schema::table('imoveis', function (Blueprint $table) {
            if (!Schema::hasColumn('imoveis', 'numero')) {
                $table->string('numero')->nullable()->after('nome');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('imoveis')) {
            return;
        }

        Schema::table('imoveis', function (Blueprint $table) {
            if (Schema::hasColumn('imoveis', 'numero')) {
                $table->dropColumn('numero');
            }
        });
    }
};

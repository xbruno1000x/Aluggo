<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('imoveis', function (Blueprint $table) {
            DB::statement("ALTER TABLE imoveis MODIFY COLUMN tipo ENUM('apartamento','terreno','loja','casa') NOT NULL");
        });
    }

    public function down(): void
    {
        Schema::table('imoveis', function (Blueprint $table) {
            DB::statement("ALTER TABLE imoveis MODIFY COLUMN tipo ENUM('apartamento','terreno','loja') NOT NULL");
        });
    }
};
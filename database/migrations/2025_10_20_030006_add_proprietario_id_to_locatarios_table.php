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
        Schema::table('locatarios', function (Blueprint $table) {
            $table->foreignId('proprietario_id')->nullable()->after('id')->constrained('proprietarios')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locatarios', function (Blueprint $table) {
            $table->dropForeign(['proprietario_id']);
            $table->dropColumn('proprietario_id');
        });
    }
};

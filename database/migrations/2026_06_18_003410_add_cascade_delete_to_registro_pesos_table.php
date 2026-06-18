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
        Schema::table('registro_pesos', function (Blueprint $table) {
            $table->dropForeign(['ganado_id']);
            $table->foreign('ganado_id')->references('id')->on('ganados')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('registro_pesos', function (Blueprint $table) {
            $table->dropForeign(['ganado_id']);
            $table->foreign('ganado_id')->references('id')->on('ganados');
        });
    }
};

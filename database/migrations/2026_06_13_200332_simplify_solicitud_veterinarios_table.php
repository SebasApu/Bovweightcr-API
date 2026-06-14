<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solicitud_veterinarios', function (Blueprint $table) {
            $table->dropColumn([
                'nombre_veterinario',
                'usuario_veterinario',
                'correo_veterinario',
                'telefono_veterinario',
                'cedula_veterinario',
            ]);

            $table->foreignId('veterinario_id')->after('ganadero_id')->constrained('users');
        });
    }

    public function down(): void
    {
        Schema::table('solicitud_veterinarios', function (Blueprint $table) {
            $table->dropForeign(['veterinario_id']);
            $table->dropColumn('veterinario_id');

            $table->string('nombre_veterinario');
            $table->string('usuario_veterinario');
            $table->string('correo_veterinario');
            $table->string('telefono_veterinario', 50);
            $table->string('cedula_veterinario', 50);
        });
    }
};

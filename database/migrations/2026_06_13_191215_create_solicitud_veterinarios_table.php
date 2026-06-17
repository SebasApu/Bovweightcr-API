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
        Schema::create('solicitud_veterinarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finca_id')->constrained('fincas')->cascadeOnDelete();
            $table->foreignId('ganadero_id')->constrained('users');
            $table->string('nombre_veterinario');
            $table->string('usuario_veterinario');
            $table->string('correo_veterinario');
            $table->string('telefono_veterinario', 50);
            $table->string('cedula_veterinario', 50);
            $table->enum('estado', ['pendiente', 'aprobado', 'rechazado'])->default('pendiente');
            $table->timestamp('aprobado_en')->nullable();
            $table->foreignId('aprobado_por')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitud_veterinarios');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otp_codes', function (Blueprint $table) {
            $table->string('correo')->primary();
            $table->string('codigo');
            $table->timestamp('expira_en');
            $table->unsignedTinyInteger('intentos')->default(0);
            $table->timestamp('bloqueado_hasta')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_codes');
    }
};

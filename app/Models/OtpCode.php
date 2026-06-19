<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Código OTP de 6 dígitos para el flujo de recuperación/cambio de
 * contraseña. Una fila por correo (se reemplaza con updateOrCreate en cada
 * envío), igual al patrón ya usado por password_reset_tokens.
 */
class OtpCode extends Model
{
    protected $table = 'otp_codes';
    protected $primaryKey = 'correo';
    public $incrementing = false;
    protected $keyType = 'string';
    public const UPDATED_AT = null;

    protected $fillable = ['correo', 'codigo', 'expira_en', 'intentos', 'bloqueado_hasta'];

    protected function casts(): array
    {
        return [
            'expira_en' => 'datetime',
            'bloqueado_hasta' => 'datetime',
        ];
    }
}

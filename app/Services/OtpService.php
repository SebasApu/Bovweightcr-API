<?php

namespace App\Services;

use App\Contracts\IUserRepository;
use App\Mail\OtpCodeMail;
use App\Models\OtpCode;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

/**
 * Genera, envía y valida códigos OTP de 6 dígitos. Es el mismo flujo para
 * "Olvidé mi contraseña" (público) y "Cambiar contraseña" (autenticado,
 * desde Perfil > Configuración) — ambos llaman a este servicio con el
 * correo del usuario.
 *
 * No sustituye a Password::broker(): al verificar el OTP se emite un token
 * estándar de Laravel para reutilizar /auth/reset-password y la vista móvil
 * ResetPasswordPage.vue ya existente, sin duplicar esa lógica.
 */
class OtpService
{
    private const VIGENCIA_MINUTOS = 2;
    private const COOLDOWN_REENVIO_SEGUNDOS = 60;
    private const MAX_INTENTOS = 5;
    private const BLOQUEO_MINUTOS = 15;

    public function __construct(
        private readonly IUserRepository $usuarios,
    ) {}

    /**
     * @throws \RuntimeException si hay que esperar para reenviar o el correo está bloqueado.
     */
    public function enviar(string $correo): void
    {
        $existente = OtpCode::find($correo);

        if ($existente?->bloqueado_hasta?->isFuture()) {
            throw new \RuntimeException('Demasiados intentos. Vuelve a intentarlo más tarde.');
        }

        if ($existente?->created_at && $existente->created_at->diffInSeconds(now()) < self::COOLDOWN_REENVIO_SEGUNDOS) {
            throw new \RuntimeException('Espera unos segundos antes de solicitar otro código.');
        }

        $usuario = $this->usuarios->findByEmail($correo);

        if (! $usuario) {
            // No revelar si el correo existe (decisión de seguridad confirmada).
            return;
        }

        $codigo = (string) random_int(100000, 999999);

        OtpCode::updateOrCreate(
            ['correo' => $correo],
            [
                'codigo' => Hash::make($codigo),
                'expira_en' => now()->addMinutes(self::VIGENCIA_MINUTOS),
                'intentos' => 0,
                'bloqueado_hasta' => null,
                'created_at' => now(),
            ],
        );

        Mail::to($usuario->correo)->send(new OtpCodeMail($usuario, $codigo));
    }

    /**
     * @throws \RuntimeException si el código es inválido, expiró o está bloqueado.
     */
    public function verificar(string $correo, string $codigo): string
    {
        $registro = OtpCode::find($correo);

        if (! $registro) {
            throw new \RuntimeException('Código inválido o expirado.');
        }

        if ($registro->bloqueado_hasta?->isFuture()) {
            throw new \RuntimeException('Demasiados intentos. Vuelve a intentarlo más tarde.');
        }

        if ($registro->expira_en->isPast()) {
            $registro->delete();
            throw new \RuntimeException('El código expiró. Solicita uno nuevo.');
        }

        if (! Hash::check($codigo, $registro->codigo)) {
            $registro->intentos++;

            if ($registro->intentos >= self::MAX_INTENTOS) {
                $registro->bloqueado_hasta = now()->addMinutes(self::BLOQUEO_MINUTOS);
            }

            $registro->save();

            throw new \RuntimeException(
                $registro->bloqueado_hasta
                    ? 'Demasiados intentos. El código se bloqueó por ' . self::BLOQUEO_MINUTOS . ' minutos.'
                    : 'Código incorrecto. Intentos restantes: ' . (self::MAX_INTENTOS - $registro->intentos),
            );
        }

        $usuario = $this->usuarios->findByEmail($correo);

        if (! $usuario) {
            throw new \RuntimeException('Código inválido o expirado.');
        }

        $registro->delete(); // un solo uso

        return Password::broker()->createToken($usuario);
    }
}

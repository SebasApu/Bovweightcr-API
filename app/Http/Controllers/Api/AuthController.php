<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use App\Services\OtpService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Maneja autenticación: login, logout, perfil y recuperación de contraseña.
 * Solo orquesta; la lógica vive en AuthService/OtpService (SRP).
 */
class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly OtpService $otpService,
    ) {}

    /**
     * POST /api/auth/login
     * Autentica al usuario y devuelve un token Sanctum (HU-01.2 / RF-02).
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $resultado = $this->authService->login(
                $request->correo,
                $request->contrasena,
            );

            return response()->json([
                'message' => 'Autenticación exitosa.',
                'token' => $resultado['token'],
                'usuario' => new UserResource($resultado['usuario']),
            ]);
        } catch (AuthenticationException $e) {
            return response()->json(['message' => $e->getMessage()], 401);
        }
    }

    /**
     * POST /api/auth/logout
     * Revoca el token actual (requiere auth:sanctum).
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json(['message' => 'Sesión cerrada correctamente.']);
    }

    /**
     * GET /api/auth/me
     * Devuelve el perfil del usuario autenticado.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json(new UserResource($request->user()->load('tipoUsuario')));
    }

    /**
     * POST /api/auth/forgot-password
     * Envía un enlace de recuperación al correo (HU-01.3 / RF-03).
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['correo' => ['required', 'email']]);

        $this->authService->enviarRecuperacion($request->correo);

        return response()->json([
            'message' => 'Si el correo existe en el sistema, recibirá un enlace de recuperación.',
        ]);
    }

    /**
     * POST /api/auth/reset-password
     * Restablece la contraseña con el token recibido por correo.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'correo' => ['required', 'email'],
            'token' => ['required', 'string'],
            'contrasena' => ['required', 'string', 'min:8'],
        ]);

        try {
            $this->authService->resetearContrasena(
                $request->correo,
                $request->token,
                $request->contrasena,
            );

            return response()->json(['message' => 'Contraseña restablecida correctamente.']);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * POST /api/auth/otp/send
     * Genera y envía un código OTP de 6 dígitos (vence en 2 minutos).
     * Mismo endpoint para "Olvidé mi contraseña" y "Cambiar contraseña".
     */
    public function sendOtp(Request $request): JsonResponse
    {
        $request->validate(['correo' => ['required', 'email']]);

        try {
            $this->otpService->enviar($request->correo);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 429);
        }

        return response()->json([
            'message' => 'Si el correo está registrado, recibirás un código de verificación.',
        ]);
    }

    /**
     * POST /api/auth/otp/verify
     * Valida el código OTP y devuelve un token de reseteo estándar de
     * Laravel para usar de inmediato con /auth/reset-password.
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $request->validate([
            'correo' => ['required', 'email'],
            'codigo' => ['required', 'string', 'size:6'],
        ]);

        try {
            $token = $this->otpService->verificar($request->correo, $request->codigo);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['token' => $token]);
    }
}

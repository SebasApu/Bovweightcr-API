<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CatalogoController;
use App\Http\Controllers\Api\EstimacionPesoController;
use App\Http\Controllers\Api\FincaController;
use App\Http\Controllers\Api\GanadoController;
use App\Http\Controllers\Api\RegistroPesoController;
use App\Http\Controllers\Api\SolicitudRegistroController;
use App\Http\Controllers\Api\SolicitudVeterinarioController;
use App\Http\Controllers\Api\UsuarioController;
use App\Http\Middleware\EsAdministrador;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Módulo 1 — Gestión de Usuarios y Autenticación
|--------------------------------------------------------------------------
|
| Rutas públicas:  Login, recuperación de contraseña, envío de solicitud.
| Rutas protegidas (auth:sanctum): logout, perfil.
| Rutas de admin (auth:sanctum + EsAdministrador): CRUD usuarios y solicitudes.
|
*/

// Rutas de fincas, ganado y solicitudes de veterinario
Route::middleware('auth:sanctum')->group(function () {

    Route::apiResource('fincas', FincaController::class);

    // Asignaciones (solo admin)
    Route::middleware(EsAdministrador::class)->group(function () {
        Route::put('fincas/{id}/ganadero', [FincaController::class, 'asignarGanadero']);
        Route::delete('fincas/{id}/ganadero', [FincaController::class, 'removerGanadero']);
        Route::put('fincas/{id}/veterinario', [FincaController::class, 'asignarVeterinario']);
        Route::delete('fincas/{id}/veterinario', [FincaController::class, 'removerVeterinario']);
    });

    Route::get('ganado', [GanadoController::class, 'index']);
    Route::post('ganado', [GanadoController::class, 'store']);
    Route::get('ganado/{id}', [GanadoController::class, 'show']);
    Route::put('ganado/{id}', [GanadoController::class, 'update']);
    Route::delete('ganado/{id}', [GanadoController::class, 'destroy']);
    Route::post('ganado/{id}/peso', [GanadoController::class, 'registrarPeso']);
    Route::patch('ganado/{id}/estado-salud', [GanadoController::class, 'actualizarEstadoSalud']);
    Route::get('ganado/{id}/historial', [RegistroPesoController::class, 'historial']);
    Route::get('pesajes/recientes', [RegistroPesoController::class, 'recientes']);

    Route::get('catalogos/estados-salud', [CatalogoController::class, 'estadosSalud']);
    Route::get('catalogos/estados-comerciales', [CatalogoController::class, 'estadosComerciales']);
    Route::get('catalogos/ganaderos', [CatalogoController::class, 'ganaderos']);
    Route::get('catalogos/veterinarios', [CatalogoController::class, 'veterinarios']);

    // Solicitudes de veterinario — creación (ganadero)
    Route::post('solicitudes-veterinario', [SolicitudVeterinarioController::class, 'store']);

});


// ── Rutas públicas ────────────────────────────────────────────────────────────
Route::prefix('auth')->name('auth.')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('forgot-password');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('reset-password');
});

Route::post('/solicitudes', [SolicitudRegistroController::class, 'store'])->name('solicitudes.store');

// Estimacion de peso (público para desarrollo)
Route::prefix('estimacion')->group(function () {
    Route::get('/health', [EstimacionPesoController::class, 'healthCheck']);
    Route::post('/estimar', [EstimacionPesoController::class, 'estimar']);
    Route::post('/estimar-batch', [EstimacionPesoController::class, 'estimarBatch']);
});

// ── Rutas protegidas (usuario autenticado) ────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
    Route::get('/auth/me', [AuthController::class, 'me'])->name('auth.me');

    Route::middleware(EsAdministrador::class)->group(function () {
        Route::apiResource('usuarios', UsuarioController::class)
            ->only(['index', 'show', 'store', 'update', 'destroy']);

        Route::get('/solicitudes', [SolicitudRegistroController::class, 'index'])->name('solicitudes.index');
        Route::get('/solicitudes/pendientes', [SolicitudRegistroController::class, 'pendientes'])->name('solicitudes.pendientes');
        Route::get('/solicitudes/{id}', [SolicitudRegistroController::class, 'show'])->name('solicitudes.show');
        Route::put('/solicitudes/{id}/revisar', [SolicitudRegistroController::class, 'revisar'])->name('solicitudes.revisar');

        // Solicitudes de veterinario — gestión admin
        Route::get('/solicitudes-veterinario', [SolicitudVeterinarioController::class, 'index']);
        Route::get('/solicitudes-veterinario/pendientes', [SolicitudVeterinarioController::class, 'pendientes']);
        Route::get('/solicitudes-veterinario/{id}', [SolicitudVeterinarioController::class, 'show']);
        Route::put('/solicitudes-veterinario/{id}/revisar', [SolicitudVeterinarioController::class, 'revisar']);
    });
});
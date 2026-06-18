<?php

namespace App\Services;

use App\Contracts\IGanadoRepository;
use App\Contracts\IRegistroPesoRepository;
use App\Models\User;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RegistroPesoService
{
    public function __construct(
        private readonly IRegistroPesoRepository $registros,
        private readonly IGanadoRepository $ganados,
        private readonly GanadoService $ganadoService,
    ) {}

    public function obtenerHistorial(int $ganadoId): Collection
    {
        if (! $this->ganados->findById($ganadoId)) {
            throw new NotFoundHttpException('Animal no encontrado.');
        }

        return $this->registros->findByGanadoId($ganadoId);
    }

    /**
     * Últimos pesajes del ganado visible para el usuario (mismo alcance que
     * GanadoService::listarTodos: dueño ve sus fincas, veterinario ve las asignadas).
     */
    public function obtenerRecientes(User $user, int $limit = 5): Collection
    {
        $ganadoIds = $this->ganadoService->listarTodos($user)->pluck('id')->all();

        return $this->registros->findRecientesByGanadoIds($ganadoIds, $limit);
    }
}

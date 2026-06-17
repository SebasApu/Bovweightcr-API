<?php

namespace App\Services;

use App\Contracts\IGanadoRepository;
use App\Contracts\IRegistroPesoRepository;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RegistroPesoService
{
    public function __construct(
        private readonly IRegistroPesoRepository $registros,
        private readonly IGanadoRepository $ganados,
    ) {}

    public function obtenerHistorial(int $ganadoId): Collection
    {
        if (! $this->ganados->findById($ganadoId)) {
            throw new NotFoundHttpException('Animal no encontrado.');
        }

        return $this->registros->findByGanadoId($ganadoId);
    }
}

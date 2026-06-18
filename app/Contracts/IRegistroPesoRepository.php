<?php

namespace App\Contracts;

use Illuminate\Support\Collection;

interface IRegistroPesoRepository
{
    public function findByGanadoId(int $ganadoId): Collection;

    public function findRecientesByGanadoIds(array $ganadoIds, int $limit): Collection;
}

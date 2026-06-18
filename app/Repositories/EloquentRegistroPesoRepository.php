<?php

namespace App\Repositories;

use App\Contracts\IRegistroPesoRepository;
use App\Models\RegistroPeso;
use Illuminate\Support\Collection;

class EloquentRegistroPesoRepository implements IRegistroPesoRepository
{
    public function findByGanadoId(int $ganadoId): Collection
    {
        return RegistroPeso::where('ganado_id', $ganadoId)
            ->orderBy('fecha', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function findRecientesByGanadoIds(array $ganadoIds, int $limit): Collection
    {
        if (empty($ganadoIds)) {
            return collect();
        }

        return RegistroPeso::with('ganado')
            ->whereIn('ganado_id', $ganadoIds)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}

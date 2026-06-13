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
}

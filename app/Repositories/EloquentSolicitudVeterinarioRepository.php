<?php

namespace App\Repositories;

use App\Contracts\ISolicitudVeterinarioRepository;
use App\Models\SolicitudVeterinario;
use Illuminate\Support\Collection;

class EloquentSolicitudVeterinarioRepository implements ISolicitudVeterinarioRepository
{
    public function findById(int $id): ?SolicitudVeterinario
    {
        return SolicitudVeterinario::with(['finca', 'ganadero', 'veterinario', 'aprobador'])->find($id);
    }

    public function findAll(): Collection
    {
        return SolicitudVeterinario::with(['finca', 'ganadero', 'veterinario', 'aprobador'])
            ->latest()
            ->get();
    }

    public function findPendientes(): Collection
    {
        return SolicitudVeterinario::with(['finca', 'ganadero', 'veterinario'])
            ->where('estado', 'pendiente')
            ->latest()
            ->get();
    }

    public function save(SolicitudVeterinario $solicitud): SolicitudVeterinario
    {
        $solicitud->save();

        return $solicitud->fresh(['finca', 'ganadero', 'veterinario', 'aprobador']);
    }
}

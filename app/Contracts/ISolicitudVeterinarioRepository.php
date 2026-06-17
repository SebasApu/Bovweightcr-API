<?php

namespace App\Contracts;

use App\Models\SolicitudVeterinario;
use Illuminate\Support\Collection;

interface ISolicitudVeterinarioRepository
{
    public function findById(int $id): ?SolicitudVeterinario;

    public function findAll(): Collection;

    public function findPendientes(): Collection;

    public function save(SolicitudVeterinario $solicitud): SolicitudVeterinario;
}

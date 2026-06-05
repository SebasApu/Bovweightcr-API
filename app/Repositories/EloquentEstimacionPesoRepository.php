<?php

namespace App\Repositories;

use App\Contracts\IEstimacionPesoRepository;
use App\Models\RegistroPeso;

class EloquentEstimacionPesoRepository implements IEstimacionPesoRepository
{
    public function save(RegistroPeso $registro): RegistroPeso
    {
        $registro->save();

        return $registro->fresh();
    }
}

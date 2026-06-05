<?php

namespace App\Contracts;

use App\Models\RegistroPeso;

interface IEstimacionPesoRepository
{
    public function save(RegistroPeso $registro): RegistroPeso;
}

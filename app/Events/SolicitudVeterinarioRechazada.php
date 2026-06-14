<?php

namespace App\Events;

use App\Models\SolicitudVeterinario;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SolicitudVeterinarioRechazada
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly SolicitudVeterinario $solicitud,
    ) {}
}

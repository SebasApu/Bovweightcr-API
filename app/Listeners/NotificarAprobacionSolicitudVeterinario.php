<?php

namespace App\Listeners;

use App\Events\SolicitudVeterinarioAprobada;
use Illuminate\Support\Facades\Log;

class NotificarAprobacionSolicitudVeterinario
{
    public function handle(SolicitudVeterinarioAprobada $event): void
    {
        Log::info('Solicitud de veterinario aprobada', [
            'solicitud_id' => $event->solicitud->id,
            'finca_id'     => $event->solicitud->finca_id,
        ]);
    }
}

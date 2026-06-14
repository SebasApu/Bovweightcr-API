<?php

namespace App\Listeners;

use App\Events\SolicitudVeterinarioRechazada;
use Illuminate\Support\Facades\Log;

class NotificarRechazoSolicitudVeterinario
{
    public function handle(SolicitudVeterinarioRechazada $event): void
    {
        Log::info('Solicitud de veterinario rechazada', [
            'solicitud_id' => $event->solicitud->id,
            'finca_id'     => $event->solicitud->finca_id,
        ]);
    }
}

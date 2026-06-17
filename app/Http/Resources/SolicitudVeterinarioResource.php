<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SolicitudVeterinarioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'finca'       => $this->whenLoaded('finca', fn () => [
                'id'     => $this->finca->id,
                'nombre' => $this->finca->nombre,
            ]),
            'ganadero'    => $this->whenLoaded('ganadero', fn () => [
                'id'     => $this->ganadero->id,
                'nombre' => $this->ganadero->nombre,
                'correo' => $this->ganadero->correo,
            ]),
            'veterinario' => $this->whenLoaded('veterinario', fn () => [
                'id'     => $this->veterinario->id,
                'nombre' => $this->veterinario->nombre,
                'correo' => $this->veterinario->correo,
            ]),
            'estado'      => $this->estado,
            'aprobado_en' => $this->aprobado_en?->toISOString(),
            'aprobador'   => $this->whenLoaded('aprobador', fn () => $this->aprobador ? [
                'id'     => $this->aprobador->id,
                'nombre' => $this->aprobador->nombre,
            ] : null),
            'creado_en'   => $this->created_at?->toISOString(),
        ];
    }
}

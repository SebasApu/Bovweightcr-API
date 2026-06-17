<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FincaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'nombre'       => $this->nombre,
            'ubicacion'    => $this->ubicacion,
            'area'         => $this->area,
            'numero_finca' => $this->numero_finca,
            'ganadero'     => $this->whenLoaded('usuario', fn () => [
                'id'     => $this->usuario->id,
                'nombre' => $this->usuario->nombre,
                'correo' => $this->usuario->correo,
            ]),
            'veterinario'  => $this->whenLoaded('veterinario', fn () => $this->veterinario ? [
                'id'     => $this->veterinario->id,
                'nombre' => $this->veterinario->nombre,
                'correo' => $this->veterinario->correo,
            ] : null),
            'total_ganado' => $this->whenLoaded('ganados', fn () => $this->ganados->count()),
            'creado_en'    => $this->created_at?->toISOString(),
        ];
    }
}

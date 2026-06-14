<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EstadoComercialGanado;
use App\Models\EstadoSaludGanado;
use App\Models\TipoUsuario;
use App\Models\User;

class CatalogoController extends Controller
{
    public function estadosSalud()
    {
        return response()->json(EstadoSaludGanado::all());
    }

    public function estadosComerciales()
    {
        return response()->json(EstadoComercialGanado::all());
    }

    public function ganaderos()
    {
        $tipo = TipoUsuario::where('nombre', 'Ganadero')->firstOrFail();

        return response()->json(
            User::where('tipo_id', $tipo->id)
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'correo'])
        );
    }

    public function veterinarios()
    {
        $tipo = TipoUsuario::where('nombre', 'Veterinario')->firstOrFail();

        return response()->json(
            User::where('tipo_id', $tipo->id)
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'correo'])
        );
    }
}

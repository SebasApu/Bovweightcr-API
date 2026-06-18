<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RegistroPesoService;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RegistroPesoController extends Controller
{
    public function __construct(
        private readonly RegistroPesoService $registroPesoService,
    ) {}

    public function historial(string $id)
    {
        try {
            return response()->json(
                $this->registroPesoService->obtenerHistorial((int) $id)
            );
        } catch (NotFoundHttpException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    public function recientes(Request $request)
    {
        $limit = (int) $request->query('limit', 5);

        return response()->json(
            $this->registroPesoService->obtenerRecientes(auth()->user(), $limit)
        );
    }
}

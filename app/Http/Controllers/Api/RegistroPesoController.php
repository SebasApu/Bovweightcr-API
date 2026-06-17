<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RegistroPesoService;
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
}

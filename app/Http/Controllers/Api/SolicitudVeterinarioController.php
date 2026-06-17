<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewSolicitudVeterinarioRequest;
use App\Http\Requests\StoreSolicitudVeterinarioRequest;
use App\Http\Resources\SolicitudVeterinarioResource;
use App\Services\SolicitudVeterinarioService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class SolicitudVeterinarioController extends Controller
{
    public function __construct(
        private readonly SolicitudVeterinarioService $solicitudService,
    ) {}

    /**
     * POST /api/solicitudes-veterinario
     * El ganadero crea una solicitud de asignación de veterinario.
     */
    public function store(StoreSolicitudVeterinarioRequest $request): JsonResponse
    {
        try {
            $solicitud = $this->solicitudService->crear(
                $request->validated(),
                auth()->user(),
            );

            return response()->json(new SolicitudVeterinarioResource($solicitud), 201);
        } catch (NotFoundHttpException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (UnprocessableEntityHttpException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * GET /api/solicitudes-veterinario
     * Lista todas las solicitudes (admin).
     */
    public function index(): JsonResponse
    {
        return response()->json(
            SolicitudVeterinarioResource::collection($this->solicitudService->listar())
        );
    }

    /**
     * GET /api/solicitudes-veterinario/pendientes
     * Lista solo las pendientes (admin).
     */
    public function pendientes(): JsonResponse
    {
        return response()->json(
            SolicitudVeterinarioResource::collection($this->solicitudService->listarPendientes())
        );
    }

    /**
     * GET /api/solicitudes-veterinario/{id}
     * Detalle de una solicitud (admin).
     */
    public function show(int $id): JsonResponse
    {
        try {
            return response()->json(
                new SolicitudVeterinarioResource($this->solicitudService->obtener($id))
            );
        } catch (NotFoundHttpException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    /**
     * PUT /api/solicitudes-veterinario/{id}/revisar
     * Admin aprueba o rechaza la solicitud.
     */
    public function revisar(ReviewSolicitudVeterinarioRequest $request, int $id): JsonResponse
    {
        try {
            $solicitud = $this->solicitudService->revisar(
                $id,
                $request->decision,
                auth()->user(),
            );

            return response()->json(new SolicitudVeterinarioResource($solicitud));
        } catch (NotFoundHttpException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (UnprocessableEntityHttpException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}

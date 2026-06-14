<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AsignarGanaderoRequest;
use App\Http\Requests\AsignarVeterinarioRequest;
use App\Http\Resources\FincaResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\FincaService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class FincaController extends Controller
{
    public function __construct(
        private readonly FincaService $fincaService,
    ) {}

    public function index(): JsonResponse
    {
        $fincas = $this->fincaService->listar(auth()->user());

        return response()->json(FincaResource::collection($fincas));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
        'usuario_id' => 'required|exists:users,id',
        'nombre' => 'required|string|max:255',
        'ubicacion' => 'required|string|max:255',
        'area' => 'required|numeric|min:0',
        'numero_finca' => 'required|string|unique:fincas,numero_finca'
    ]);

        try {
            $finca = $this->fincaService->crear($validated);

            return response()->json([
                'message' => 'Finca registrada correctamente',
                'data'    => new FincaResource($finca),
            ], 201);
        } catch (ConflictHttpException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $finca = $this->fincaService->obtener((int) $id);

            return response()->json(new FincaResource($finca));
        } catch (NotFoundHttpException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'usuario_id' => 'required|exists:users,id',
            'nombre' => 'required|string|max:255',
            'ubicacion' => 'required|string|max:255',
            'area' => 'required|numeric|min:0',
            'numero_finca' => 'required|string|unique:fincas,numero_finca,' . $id,
        ]);
        try {
            $finca = $this->fincaService->actualizar((int) $id, $validated);

            return response()->json([
                'message' => 'Finca actualizada correctamente',
                'data'    => new FincaResource($finca),
            ]);
        } catch (NotFoundHttpException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (ConflictHttpException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $this->fincaService->eliminar((int) $id);

            return response()->json(['message' => 'Finca eliminada correctamente']);
        } catch (NotFoundHttpException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (BadRequestHttpException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * PUT /api/fincas/{id}/ganadero
     * Admin asigna o cambia el ganadero de una finca.
     */
    public function asignarGanadero(AsignarGanaderoRequest $request, string $id): JsonResponse
    {
        try {
            $finca = $this->fincaService->asignarGanadero((int) $id, $request->usuario_id);

            return response()->json([
                'message' => 'Ganadero asignado correctamente',
                'data'    => new FincaResource($finca),
            ]);
        } catch (NotFoundHttpException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (UnprocessableEntityHttpException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * DELETE /api/fincas/{id}/ganadero
     * Admin remueve el ganadero de una finca.
     */
    public function removerGanadero(string $id): JsonResponse
    {
        try {
            $finca = $this->fincaService->removerGanadero((int) $id);

            return response()->json([
                'message' => 'Ganadero removido correctamente',
                'data'    => new FincaResource($finca),
            ]);
        } catch (NotFoundHttpException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    /**
     * PUT /api/fincas/{id}/veterinario
     * Admin asigna o cambia el veterinario de una finca.
     */
    public function asignarVeterinario(AsignarVeterinarioRequest $request, string $id): JsonResponse
    {
        try {
            $finca = $this->fincaService->asignarVeterinario((int) $id, $request->usuario_id);

            return response()->json([
                'message' => 'Veterinario asignado correctamente',
                'data'    => new FincaResource($finca),
            ]);
        } catch (NotFoundHttpException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (UnprocessableEntityHttpException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * DELETE /api/fincas/{id}/veterinario
     * Admin remueve el veterinario de una finca.
     */
    public function removerVeterinario(string $id): JsonResponse
    {
        try {
            $finca = $this->fincaService->removerVeterinario((int) $id);

            return response()->json([
                'message' => 'Veterinario removido correctamente',
                'data'    => new FincaResource($finca),
            ]);
        } catch (NotFoundHttpException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }
}

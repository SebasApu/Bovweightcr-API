<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GanadoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class GanadoController extends Controller
{
    public function __construct(
        private readonly GanadoService $ganadoService,
    ) {}

    public function index(Request $request)
    {
        $user = auth()->user();

        if ($request->has('finca_id')) {
            try {
                return response()->json(
                    $this->ganadoService->listarPorFinca((int) $request->query('finca_id'), $user)
                );
            } catch (AccessDeniedHttpException $e) {
                return response()->json(['message' => $e->getMessage()], 403);
            } catch (NotFoundHttpException $e) {
                return response()->json(['message' => $e->getMessage()], 404);
            }
        }

        return response()->json($this->ganadoService->listarTodos($user));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'finca_id'            => 'required|exists:fincas,id',
            'estado_salud_id'     => 'required|exists:estado_salud_ganados,id',
            'estado_comercial_id' => 'required|exists:estado_comercial_ganados,id',
            'arete'               => 'required|string',
            'nombre'              => 'nullable|string|max:255',
            'sexo'                => 'nullable|in:Macho,Hembra',
            'raza'                => 'required|string|max:255',
            'imagen'              => 'nullable|string',
        ]);

        if (! empty($validated['imagen']) && $this->esBase64($validated['imagen'])) {
            $validated['imagen'] = $this->guardarImagen($validated['imagen']);
        }

        try {
            $ganado = $this->ganadoService->crear($validated, auth()->user());

            return response()->json([
                'message' => 'Animal registrado correctamente',
                'data'    => $ganado,
            ], 201);
        } catch (AccessDeniedHttpException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (ConflictHttpException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'finca_id'            => 'sometimes|exists:fincas,id',
            'estado_salud_id'     => 'sometimes|exists:estado_salud_ganados,id',
            'estado_comercial_id' => 'sometimes|exists:estado_comercial_ganados,id',
            'arete'               => 'sometimes|string',
            'nombre'              => 'nullable|string|max:255',
            'sexo'                => 'nullable|in:Macho,Hembra',
            'raza'                => 'sometimes|string|max:255',
            'imagen'              => 'nullable|string',
        ]);

        if (isset($validated['imagen']) && $this->esBase64($validated['imagen'])) {
            $imagenAnterior = $this->ganadoService->obtener((int) $id)->imagen;
            if ($imagenAnterior) {
                $this->eliminarImagen($imagenAnterior);
            }
            $validated['imagen'] = $this->guardarImagen($validated['imagen']);
        }

        try {
            $ganado = $this->ganadoService->actualizar((int) $id, $validated, auth()->user());

            return response()->json([
                'message' => 'Animal actualizado correctamente',
                'data'    => $ganado,
            ]);
        } catch (AccessDeniedHttpException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (ConflictHttpException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        } catch (NotFoundHttpException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    public function show(string $id)
    {
        try {
            return response()->json(
                $this->ganadoService->obtener((int) $id)
            );
        } catch (NotFoundHttpException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    public function registrarPeso(Request $request, string $id)
    {
        $validated = $request->validate([
            'peso' => 'required|numeric|min:0',
        ]);

        try {
            $registro = $this->ganadoService->registrarPeso(
                (int) $id,
                (float) $validated['peso'],
                auth()->user(),
            );

            return response()->json([
                'message' => 'Peso registrado correctamente',
                'data'    => $registro,
            ], 201);
        } catch (AccessDeniedHttpException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (NotFoundHttpException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    public function actualizarEstadoSalud(Request $request, string $id)
    {
        $validated = $request->validate([
            'estado_salud_id' => 'required|exists:estado_salud_ganados,id',
        ]);

        try {
            $ganado = $this->ganadoService->actualizarEstadoSalud(
                (int) $id,
                (int) $validated['estado_salud_id'],
                auth()->user(),
            );

            return response()->json([
                'message' => 'Estado de salud actualizado correctamente',
                'data'    => $ganado,
            ]);
        } catch (AccessDeniedHttpException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (NotFoundHttpException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    public function destroy(string $id)
    {
        try {
            $ganado = $this->ganadoService->obtener((int) $id);

            if ($ganado->imagen) {
                $this->eliminarImagen($ganado->imagen);
            }

            $this->ganadoService->eliminar((int) $id);

            return response()->json(['message' => 'Animal eliminado correctamente']);
        } catch (NotFoundHttpException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    private function esBase64(string $valor): bool
    {
        return str_starts_with($valor, 'data:');
    }

    private function guardarImagen(string $base64Uri): string
    {
        $base64 = preg_replace('/^data:image\/\w+;base64,/', '', $base64Uri);
        $decoded = base64_decode($base64);
        $filename = 'ganado_' . uniqid() . '.jpg';
        Storage::disk('s3')->put("ganado/{$filename}", $decoded);

        return Storage::disk('s3')->url("ganado/{$filename}");
    }

    private function eliminarImagen(string $imagenUrl): void
    {
        $path = parse_url($imagenUrl, PHP_URL_PATH);
        $relativa = ltrim(str_replace('/storage', '', $path), '/');
        Storage::disk('s3')->delete($relativa);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\EstimacionPesoService;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class EstimacionPesoController extends Controller
{
    public function __construct(
        private readonly EstimacionPesoService $estimacionService,
    ) {}

    public function estimar(Request $request)
    {
        $validated = $request->validate([
            'image'       => 'required|image|max:10240',
            'ganado_id'   => 'required|exists:ganados,id',
            'breed'       => 'nullable|string|in:brahman,cebu,criollo,holstein,jersey,angus,hereford,simmental,default',
            'distance_cm' => 'nullable|numeric',
            'camera_fov'  => 'nullable|numeric',
        ]);

        try {
            $result = $this->estimacionService->estimar(
                ganadoId:   $validated['ganado_id'],
                imagen:     $request->file('image'),
                breed:      $validated['breed'] ?? 'default',
                distanceCm: (float) ($validated['distance_cm'] ?? 500),
                cameraFov:  (float) ($validated['camera_fov'] ?? 24),
            );

            return response()->json($result, 201);
        } catch (ServiceUnavailableHttpException $e) {
            return response()->json(['error' => $e->getMessage()], 503);
        } catch (HttpException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getStatusCode());
        }
    }

    public function estimarBatch(Request $request)
    {
        $validated = $request->validate([
            'images'      => 'required|array|min:2|max:5',
            'images.*'    => 'image|max:10240',
            'ganado_id'   => 'required|exists:ganados,id',
            'breed'       => 'nullable|string|in:brahman,cebu,criollo,holstein,jersey,angus,hereford,simmental,default',
            'distance_cm' => 'nullable|numeric',
            'camera_fov'  => 'nullable|numeric',
        ]);

        try {
            $result = $this->estimacionService->estimarBatch(
                ganadoId:   $validated['ganado_id'],
                imagenes:   $request->file('images'),
                breed:      $validated['breed'] ?? 'default',
                distanceCm: (float) ($validated['distance_cm'] ?? 500),
                cameraFov:  (float) ($validated['camera_fov'] ?? 24),
            );

            return response()->json($result, 201);
        } catch (ServiceUnavailableHttpException $e) {
            return response()->json(['error' => $e->getMessage()], 503);
        } catch (HttpException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getStatusCode());
        }
    }

    public function healthCheck()
    {
        try {
            return response()->json($this->estimacionService->healthCheck());
        } catch (ServiceUnavailableHttpException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 503);
        }
    }
}

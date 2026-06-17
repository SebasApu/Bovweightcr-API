<?php

namespace App\Services;

use App\Contracts\IEstimacionPesoRepository;
use App\Models\RegistroPeso;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class EstimacionPesoService
{
    private string $mlServiceUrl;

    public function __construct(
        private readonly IEstimacionPesoRepository $registros,
    ) {
        $this->mlServiceUrl = config('services.ml.url', 'http://127.0.0.1:5000');
    }

    public function estimar(int $ganadoId, UploadedFile $imagen, string $breed, float $distanceCm, float $cameraFov): array
    {
        $path = $imagen->store('estimaciones', 's3');

        try {
            $response = Http::timeout(60)
                ->attach('image', file_get_contents($imagen->path()), $imagen->getClientOriginalName())
                ->attach('breed', $breed)
                ->attach('distance_cm', (string) $distanceCm)
                ->attach('camera_fov', (string) $cameraFov)
                ->post("{$this->mlServiceUrl}/api/estimate");
        } catch (\Exception $e) {
            Storage::disk('s3')->delete($path);
            throw new ServiceUnavailableHttpException(message: 'No se pudo conectar con el servicio de estimacion.');
        }

        if (!$response->successful()) {
            Storage::disk('s3')->delete($path);
            throw new HttpException($response->status(), 'Error en la estimacion.');
        }

        $data = $response->json();

        $registro = new RegistroPeso([
            'ganado_id'       => $ganadoId,
            'peso_estimado'   => $data['peso_estimado_kg'] ?? 0,
            'fecha'           => now(),
            'confianza'       => $data['confianza'] ?? 0,
            'metodo'          => $data['metodo'] ?? 'unknown',
            'imagen_path'     => $path,
            'medidas'         => $data['medidas'] ?? null,
            'raza_estimacion' => $breed,
        ]);

        return [
            'registro'    => $this->registros->save($registro),
            'estimacion'  => $data,
            'advertencia' => $data['advertencia'] ?? 'Estimacion aproximada.',
        ];
    }

    public function estimarBatch(int $ganadoId, array $imagenes, string $breed, float $distanceCm, float $cameraFov): array
    {
        $httpRequest = Http::timeout(120);

        foreach ($imagenes as $imagen) {
            $httpRequest = $httpRequest->attach(
                'images',
                file_get_contents($imagen->path()),
                $imagen->getClientOriginalName()
            );
        }

        try {
            $response = $httpRequest
                ->attach('breed', $breed)
                ->attach('distance_cm', (string) $distanceCm)
                ->attach('camera_fov', (string) $cameraFov)
                ->post("{$this->mlServiceUrl}/api/estimate/batch");
        } catch (\Exception $e) {
            throw new ServiceUnavailableHttpException(message: 'No se pudo conectar con el servicio de estimacion.');
        }

        if (!$response->successful()) {
            throw new HttpException($response->status(), 'Error en la estimacion.');
        }

        $data = $response->json();

        $path = $imagenes[0]->store('estimaciones', 's3');

        $registro = new RegistroPeso([
            'ganado_id'       => $ganadoId,
            'peso_estimado'   => $data['peso_estimado_kg'],
            'fecha'           => now(),
            'confianza'       => 0.75,
            'metodo'          => 'batch_average',
            'imagen_path'     => $path,
            'medidas'         => ['pesos_individuales' => $data['pesos_individuales']],
            'raza_estimacion' => $breed,
        ]);

        return [
            'registro'   => $this->registros->save($registro),
            'estimacion' => $data,
        ];
    }

    public function healthCheck(): array
    {
        try {
            $response = Http::timeout(5)->get("{$this->mlServiceUrl}/api/health");

            return $response->json();
        } catch (\Exception $e) {
            throw new ServiceUnavailableHttpException(message: 'Microservicio ML no disponible.');
        }
    }
}

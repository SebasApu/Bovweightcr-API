<?php

namespace Tests\Feature\Controllers\EstimacionPesoControllerTest;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_health_check_exitoso(): void
    {
        Http::fake([
            '*/api/health' => Http::response([
                'status' => 'ok',
                'model_loaded' => true,
            ], 200),
        ]);

        $response = $this->getJson('/api/estimacion/health');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('model_loaded', true);
    }

    public function test_health_check_fallido_cuando_ml_offline(): void
    {
        Http::fake([
            '*/api/health' => fn() => throw new \Illuminate\Http\Client\ConnectionException('Connection refused'),
        ]);

        $response = $this->getJson('/api/estimacion/health');

        $response->assertStatus(503)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'Microservicio ML no disponible.');
    }
}

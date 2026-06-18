<?php

namespace Tests\Feature\Controllers\EstimacionPesoControllerTest;

use App\Models\EstadoComercialGanado;
use App\Models\EstadoSaludGanado;
use App\Models\Finca;
use App\Models\Ganado;
use App\Models\User;
use Database\Seeders\EstadoComercialGanadoSeeder;
use Database\Seeders\EstadoSaludGanadoSeeder;
use Database\Seeders\TipoUsuarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EstimarBatchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TipoUsuarioSeeder::class);
        $this->seed(EstadoSaludGanadoSeeder::class);
        $this->seed(EstadoComercialGanadoSeeder::class);
        Storage::fake('public');
        Storage::fake('s3');
    }

    public function test_estimar_batch_exitoso_crea_registro_de_peso_y_retorna_estimacion(): void
    {
        $usuario = User::factory()->ganadero()->create();
        $finca = Finca::create([
            'usuario_id' => $usuario->id,
            'nombre' => 'Finca Central',
            'ubicacion' => 'Puntarenas',
            'area' => 75.0,
            'numero_finca' => 'CR-5001',
        ]);

        $salud = EstadoSaludGanado::first();
        $comercial = EstadoComercialGanado::first();

        $ganado = Ganado::create([
            'finca_id' => $finca->id,
            'estado_salud_id' => $salud->id,
            'estado_comercial_id' => $comercial->id,
            'arete' => 'ART-101',
            'nombre' => 'Pinta',
            'sexo' => 'Hembra',
            'raza' => 'Holstein',
        ]);

        Http::fake([
            '*/api/estimate/batch' => Http::response([
                'peso_estimado_kg' => 395.0,
                'pesos_individuales' => [390.0, 400.0],
                'num_imagenes_procesadas' => 2,
                'advertencia' => 'Estimacion aproximada.',
            ], 200),
        ]);

        $imagen1 = UploadedFile::fake()->image('cow1.jpg');
        $imagen2 = UploadedFile::fake()->image('cow2.jpg');

        $response = $this->postJson('/api/estimacion/estimar-batch', [
            'images' => [$imagen1, $imagen2],
            'ganado_id' => $ganado->id,
            'breed' => 'default',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'registro' => ['id', 'peso_estimado', 'ganado_id'],
                'estimacion' => ['peso_estimado_kg', 'pesos_individuales'],
            ]);

        $this->assertDatabaseHas('registro_pesos', [
            'ganado_id' => $ganado->id,
            'peso_estimado' => 395.0,
        ]);
    }

    public function test_estimar_batch_con_ml_offline_devuelve_503(): void
    {
        $usuario = User::factory()->ganadero()->create();
        $finca = Finca::create([
            'usuario_id' => $usuario->id,
            'nombre' => 'Finca Central',
            'ubicacion' => 'Puntarenas',
            'area' => 75.0,
            'numero_finca' => 'CR-5001',
        ]);

        $salud = EstadoSaludGanado::first();
        $comercial = EstadoComercialGanado::first();

        $ganado = Ganado::create([
            'finca_id' => $finca->id,
            'estado_salud_id' => $salud->id,
            'estado_comercial_id' => $comercial->id,
            'arete' => 'ART-101',
            'nombre' => 'Pinta',
            'sexo' => 'Hembra',
            'raza' => 'Holstein',
        ]);

        Http::fake([
            '*/api/estimate/batch' => fn() => throw new \Illuminate\Http\Client\ConnectionException('Connection refused'),
        ]);

        $imagen1 = UploadedFile::fake()->image('cow1.jpg');
        $imagen2 = UploadedFile::fake()->image('cow2.jpg');

        $response = $this->postJson('/api/estimacion/estimar-batch', [
            'images' => [$imagen1, $imagen2],
            'ganado_id' => $ganado->id,
            'breed' => 'default',
        ]);

        $response->assertStatus(503);
    }

    public function test_estimar_batch_sin_imagenes_suficientes_devuelve_422(): void
    {
        $response = $this->postJson('/api/estimacion/estimar-batch', [
            'images' => [UploadedFile::fake()->image('cow1.jpg')], // Solo una imagen
            'ganado_id' => 1,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['images']);
    }
}

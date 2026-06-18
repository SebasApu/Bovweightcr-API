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

class EstimarTest extends TestCase
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

    public function test_estimar_exitoso_crea_registro_de_peso_y_retorna_estimacion(): void
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
            '*/api/estimate' => Http::response([
                'peso_estimado_kg' => 380.5,
                'rango_min_kg' => 360,
                'rango_max_kg' => 400,
                'confianza' => 0.92,
                'metodo' => 'reference_object',
                'medidas' => [
                    'perimetro_toracico_cm' => 170.0,
                    'largo_cuerpo_cm' => 140.0,
                    'altura_cm' => 135.0,
                ],
                'referencia_detectada' => true,
                'deteccion' => [
                    'bbox' => [100, 100, 500, 400],
                    'score' => 0.95,
                ],
            ], 200),
        ]);

        $imagen = UploadedFile::fake()->image('cow.jpg');

        $response = $this->postJson('/api/estimacion/estimar', [
            'image' => $imagen,
            'ganado_id' => $ganado->id,
            'breed' => 'default',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'registro' => ['id', 'peso_estimado', 'ganado_id'],
                'estimacion' => ['peso_estimado_kg', 'confianza'],
            ]);

        $this->assertDatabaseHas('registro_pesos', [
            'ganado_id' => $ganado->id,
            'peso_estimado' => 380.5,
        ]);
    }

    public function test_estimar_con_ml_offline_devuelve_503(): void
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
            '*/api/estimate' => fn() => throw new \Illuminate\Http\Client\ConnectionException('Connection refused'),
        ]);

        $imagen = UploadedFile::fake()->image('cow.jpg');

        $response = $this->postJson('/api/estimacion/estimar', [
            'image' => $imagen,
            'ganado_id' => $ganado->id,
            'breed' => 'default',
        ]);

        $response->assertStatus(503);
    }

    public function test_estimar_sin_imagen_o_datos_devuelve_422(): void
    {
        $response = $this->postJson('/api/estimacion/estimar', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image', 'ganado_id']);
    }
}

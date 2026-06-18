<?php

namespace Tests\Feature\Controllers\GanadoControllerTest;

use App\Models\EstadoComercialGanado;
use App\Models\EstadoSaludGanado;
use App\Models\Finca;
use App\Models\User;
use Database\Seeders\EstadoComercialGanadoSeeder;
use Database\Seeders\EstadoSaludGanadoSeeder;
use Database\Seeders\TipoUsuarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TipoUsuarioSeeder::class);
        $this->seed(EstadoSaludGanadoSeeder::class);
        $this->seed(EstadoComercialGanadoSeeder::class);
    }

    public function test_usuario_puede_crear_ganado(): void
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

        $response = $this->actingAs($usuario)
            ->postJson('/api/ganado', [
                'finca_id' => $finca->id,
                'estado_salud_id' => $salud->id,
                'estado_comercial_id' => $comercial->id,
                'arete' => 'ART-NEW',
                'nombre' => 'Vaca Lola',
                'sexo' => 'Hembra',
                'raza' => 'Jersey',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Animal registrado correctamente');

        $this->assertDatabaseHas('ganados', ['arete' => 'ART-NEW']);
    }

    public function test_no_dueno_no_puede_crear_ganado_en_finca_ajena(): void
    {
        $ganadero1 = User::factory()->ganadero()->create();
        $ganadero2 = User::factory()->ganadero()->create();

        $finca = Finca::create([
            'usuario_id' => $ganadero2->id,
            'nombre' => 'Finca Central',
            'ubicacion' => 'Puntarenas',
            'area' => 75.0,
            'numero_finca' => 'CR-5001',
        ]);

        $salud = EstadoSaludGanado::first();
        $comercial = EstadoComercialGanado::first();

        $response = $this->actingAs($ganadero1)
            ->postJson('/api/ganado', [
                'finca_id' => $finca->id,
                'estado_salud_id' => $salud->id,
                'estado_comercial_id' => $comercial->id,
                'arete' => 'ART-NEW',
                'nombre' => 'Vaca Lola',
                'sexo' => 'Hembra',
                'raza' => 'Jersey',
            ]);

        $response->assertStatus(403);
    }

    public function test_crear_ganado_con_arete_duplicado_devuelve_409(): void
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

        // Crear ganado inicial
        \App\Models\Ganado::create([
            'finca_id' => $finca->id,
            'estado_salud_id' => $salud->id,
            'estado_comercial_id' => $comercial->id,
            'arete' => 'ART-DUPLICADO',
            'nombre' => 'Toro 1',
            'sexo' => 'Macho',
            'raza' => 'Brahman',
        ]);

        $response = $this->actingAs($usuario)
            ->postJson('/api/ganado', [
                'finca_id' => $finca->id,
                'estado_salud_id' => $salud->id,
                'estado_comercial_id' => $comercial->id,
                'arete' => 'ART-DUPLICADO',
                'nombre' => 'Vaca Lola',
                'sexo' => 'Hembra',
                'raza' => 'Jersey',
            ]);

        $response->assertStatus(409);
    }

    public function test_crear_ganado_sin_datos_requeridos_devuelve_422(): void
    {
        $usuario = User::factory()->ganadero()->create();

        $response = $this->actingAs($usuario)
            ->postJson('/api/ganado', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['finca_id', 'estado_salud_id', 'estado_comercial_id', 'arete', 'raza']);
    }
}

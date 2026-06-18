<?php

namespace Tests\Feature\Controllers\GanadoControllerTest;

use App\Models\EstadoComercialGanado;
use App\Models\EstadoSaludGanado;
use App\Models\Finca;
use App\Models\Ganado;
use App\Models\User;
use Database\Seeders\EstadoComercialGanadoSeeder;
use Database\Seeders\EstadoSaludGanadoSeeder;
use Database\Seeders\TipoUsuarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TipoUsuarioSeeder::class);
        $this->seed(EstadoSaludGanadoSeeder::class);
        $this->seed(EstadoComercialGanadoSeeder::class);
    }

    public function test_usuario_autenticado_puede_listar_animales(): void
    {
        $usuario = User::factory()->ganadero()->create();

        $response = $this->actingAs($usuario)
            ->getJson('/api/ganado');

        $response->assertStatus(200)
            ->assertJsonIsArray();
    }

    public function test_usuario_no_autenticado_no_puede_listar_animales(): void
    {
        $this->getJson('/api/ganado')->assertStatus(401);
    }

    public function test_puede_listar_ganado_filtrado_por_finca_id(): void
    {
        $ganadero = User::factory()->ganadero()->create();

        $finca1 = Finca::create([
            'usuario_id' => $ganadero->id,
            'nombre' => 'Finca A',
            'ubicacion' => 'Ubicacion A',
            'area' => 10,
            'numero_finca' => 'CR-A',
        ]);
        $finca2 = Finca::create([
            'usuario_id' => $ganadero->id,
            'nombre' => 'Finca B',
            'ubicacion' => 'Ubicacion B',
            'area' => 20,
            'numero_finca' => 'CR-B',
        ]);

        $salud = EstadoSaludGanado::first();
        $comercial = EstadoComercialGanado::first();

        // Ganado en Finca 1
        Ganado::create([
            'finca_id' => $finca1->id,
            'estado_salud_id' => $salud->id,
            'estado_comercial_id' => $comercial->id,
            'arete' => 'ART-F1',
            'nombre' => 'Animal F1',
            'sexo' => 'Macho',
            'raza' => 'Brahman',
        ]);

        // Ganado en Finca 2
        Ganado::create([
            'finca_id' => $finca2->id,
            'estado_salud_id' => $salud->id,
            'estado_comercial_id' => $comercial->id,
            'arete' => 'ART-F2',
            'nombre' => 'Animal F2',
            'sexo' => 'Hembra',
            'raza' => 'Jersey',
        ]);

        $response = $this->actingAs($ganadero)
            ->getJson('/api/ganado?finca_id=' . $finca1->id);

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonPath('0.arete', 'ART-F1');
    }
}

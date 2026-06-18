<?php

namespace Tests\Feature\Controllers\FincaControllerTest;

use App\Models\Finca;
use App\Models\User;
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
    }

    public function test_usuario_puede_crear_finca(): void
    {
        $usuario = User::factory()->ganadero()->create();

        $response = $this->actingAs($usuario)
            ->postJson('/api/fincas', [
                'usuario_id' => $usuario->id,
                'nombre' => 'Nueva Finca',
                'ubicacion' => 'San José',
                'area' => 120.0,
                'numero_finca' => 'CR-2026',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Finca registrada correctamente')
            ->assertJsonStructure(['data' => ['id', 'nombre', 'numero_finca']]);

        $this->assertDatabaseHas('fincas', ['numero_finca' => 'CR-2026']);
    }

    public function test_crear_finca_con_numero_duplicado_devuelve_422(): void
    {
        $usuario = User::factory()->ganadero()->create();
        Finca::create([
            'usuario_id' => $usuario->id,
            'nombre' => 'Finca Existente',
            'ubicacion' => 'Heredia',
            'area' => 90.0,
            'numero_finca' => 'CR-DUPLICADO',
        ]);

        $response = $this->actingAs($usuario)
            ->postJson('/api/fincas', [
                'usuario_id' => $usuario->id,
                'nombre' => 'Finca Nueva',
                'ubicacion' => 'Alajuela',
                'area' => 45.0,
                'numero_finca' => 'CR-DUPLICADO',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['numero_finca']);
    }

    public function test_crear_finca_sin_datos_requeridos_devuelve_422(): void
    {
        $usuario = User::factory()->ganadero()->create();

        $response = $this->actingAs($usuario)
            ->postJson('/api/fincas', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['usuario_id', 'nombre', 'ubicacion', 'area', 'numero_finca']);
    }
}

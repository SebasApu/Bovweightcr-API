<?php

namespace Tests\Feature\Controllers\FincaControllerTest;

use App\Models\Finca;
use App\Models\User;
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
    }

    public function test_usuario_no_autenticado_no_puede_listar_fincas(): void
    {
        $this->getJson('/api/fincas')->assertStatus(401);
    }

    public function test_ganadero_puede_listar_sus_fincas(): void
    {
        $ganadero1 = User::factory()->ganadero()->create();
        $ganadero2 = User::factory()->ganadero()->create();

        // Finca de ganadero 1
        $finca1 = Finca::create([
            'usuario_id' => $ganadero1->id,
            'nombre' => 'Finca Sol',
            'ubicacion' => 'Guanacaste',
            'area' => 150.5,
            'numero_finca' => 'CR-1001',
        ]);

        // Finca de ganadero 2
        $finca2 = Finca::create([
            'usuario_id' => $ganadero2->id,
            'nombre' => 'Finca Luna',
            'ubicacion' => 'Alajuela',
            'area' => 80.0,
            'numero_finca' => 'CR-1002',
        ]);

        $response = $this->actingAs($ganadero1)
            ->getJson('/api/fincas');

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonPath('0.numero_finca', 'CR-1001');
    }

    public function test_veterinario_puede_listar_sus_fincas_asignadas(): void
    {
        $ganadero = User::factory()->ganadero()->create();
        $veterinario1 = User::factory()->veterinario()->create();
        $veterinario2 = User::factory()->veterinario()->create();

        $finca1 = Finca::create([
            'usuario_id' => $ganadero->id,
            'veterinario_id' => $veterinario1->id,
            'nombre' => 'Finca 1',
            'ubicacion' => 'Guanacaste',
            'area' => 100,
            'numero_finca' => 'CR-V1',
        ]);

        $finca2 = Finca::create([
            'usuario_id' => $ganadero->id,
            'veterinario_id' => $veterinario2->id,
            'nombre' => 'Finca 2',
            'ubicacion' => 'Guanacaste',
            'area' => 200,
            'numero_finca' => 'CR-V2',
        ]);

        $response = $this->actingAs($veterinario1)
            ->getJson('/api/fincas');

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonPath('0.numero_finca', 'CR-V1');
    }

    public function test_admin_puede_listar_todas_las_fincas(): void
    {
        $admin = User::factory()->administrador()->create();
        $ganadero1 = User::factory()->ganadero()->create();
        $ganadero2 = User::factory()->ganadero()->create();

        Finca::create([
            'usuario_id' => $ganadero1->id,
            'nombre' => 'Finca 1',
            'ubicacion' => 'Ubicacion 1',
            'area' => 50,
            'numero_finca' => 'CR-A1',
        ]);

        Finca::create([
            'usuario_id' => $ganadero2->id,
            'nombre' => 'Finca 2',
            'ubicacion' => 'Ubicacion 2',
            'area' => 60,
            'numero_finca' => 'CR-A2',
        ]);

        $response = $this->actingAs($admin)
            ->getJson('/api/fincas');

        $response->assertStatus(200)
            ->assertJsonCount(2);
    }
}

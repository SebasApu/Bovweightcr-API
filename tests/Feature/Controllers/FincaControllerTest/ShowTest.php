<?php

namespace Tests\Feature\Controllers\FincaControllerTest;

use App\Models\Finca;
use App\Models\User;
use Database\Seeders\TipoUsuarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TipoUsuarioSeeder::class);
    }

    public function test_usuario_puede_ver_finca(): void
    {
        $usuario = User::factory()->ganadero()->create();
        $finca = Finca::create([
            'usuario_id' => $usuario->id,
            'nombre' => 'Finca Central',
            'ubicacion' => 'Puntarenas',
            'area' => 75.0,
            'numero_finca' => 'CR-5001',
        ]);

        $response = $this->actingAs($usuario)
            ->getJson("/api/fincas/{$finca->id}");

        $response->assertStatus(200)
            ->assertJsonPath('numero_finca', 'CR-5001');
    }

    public function test_ver_finca_inexistente_devuelve_404(): void
    {
        $usuario = User::factory()->ganadero()->create();

        $response = $this->actingAs($usuario)
            ->getJson('/api/fincas/9999');

        $response->assertStatus(404);
    }
}

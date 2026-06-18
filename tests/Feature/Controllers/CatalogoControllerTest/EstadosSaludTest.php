<?php

namespace Tests\Feature\Controllers\CatalogoControllerTest;

use App\Models\User;
use Database\Seeders\EstadoSaludGanadoSeeder;
use Database\Seeders\TipoUsuarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EstadosSaludTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TipoUsuarioSeeder::class);
        $this->seed(EstadoSaludGanadoSeeder::class);
    }

    public function test_usuario_puede_obtener_estados_de_salud(): void
    {
        $usuario = User::factory()->ganadero()->create();

        $response = $this->actingAs($usuario)
            ->getJson('/api/catalogos/estados-salud');

        $response->assertStatus(200)
            ->assertJsonIsArray()
            ->assertJsonCount(3); // Sano, En tratamiento, Crítico (según seeder)
    }

    public function test_usuario_no_autenticado_no_puede_obtener_estados_de_salud(): void
    {
        $this->getJson('/api/catalogos/estados-salud')
            ->assertStatus(401);
    }
}

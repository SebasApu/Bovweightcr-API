<?php

namespace Tests\Feature\Controllers\AuthControllerTest;

use App\Models\User;
use Database\Seeders\TipoUsuarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TipoUsuarioSeeder::class);
    }

    public function test_me_devuelve_usuario_autenticado(): void
    {
        $usuario = User::factory()->create();

        $response = $this->actingAs($usuario)
            ->getJson('/api/auth/me');

        $response->assertStatus(200)
            ->assertJsonPath('correo', $usuario->correo);
    }

    public function test_me_sin_autenticacion_devuelve_401(): void
    {
        $this->getJson('/api/auth/me')->assertStatus(401);
    }
}

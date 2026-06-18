<?php

namespace Tests\Feature\Controllers\UsuarioControllerTest;

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

    public function test_no_autenticado_no_puede_listar_usuarios(): void
    {
        $this->getJson('/api/usuarios')->assertStatus(401);
    }

    public function test_ganadero_no_puede_listar_usuarios(): void
    {
        $ganadero = User::factory()->ganadero()->create();

        $this->actingAs($ganadero)
            ->getJson('/api/usuarios')
            ->assertStatus(403);
    }

    public function test_admin_puede_listar_usuarios(): void
    {
        $admin = User::factory()->administrador()->create();
        User::factory()->ganadero()->count(3)->create();

        $response = $this->actingAs($admin)
            ->getJson('/api/usuarios');

        $response->assertStatus(200)
            ->assertJsonCount(4); // 3 ganaderos + el admin mismo
    }

    public function test_admin_puede_buscar_usuarios_por_nombre(): void
    {
        $admin = User::factory()->administrador()->create();
        User::factory()->ganadero()->create(['nombre' => 'Juan Perez']);
        User::factory()->ganadero()->create(['nombre' => 'Maria Lopez']);

        $response = $this->actingAs($admin)
            ->getJson('/api/usuarios?buscar=Juan');

        $response->assertStatus(200)
            ->assertJsonCount(1);
    }
}

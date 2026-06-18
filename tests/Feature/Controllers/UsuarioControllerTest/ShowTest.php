<?php

namespace Tests\Feature\Controllers\UsuarioControllerTest;

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

    public function test_admin_puede_ver_usuario_por_id(): void
    {
        $admin = User::factory()->administrador()->create();
        $ganadero = User::factory()->ganadero()->create();

        $this->actingAs($admin)
            ->getJson("/api/usuarios/{$ganadero->id}")
            ->assertStatus(200)
            ->assertJsonPath('correo', $ganadero->correo);
    }

    public function test_show_usuario_inexistente_devuelve_404(): void
    {
        $admin = User::factory()->administrador()->create();

        $this->actingAs($admin)
            ->getJson('/api/usuarios/9999')
            ->assertStatus(404);
    }
}

<?php

namespace Tests\Feature\Controllers\UsuarioControllerTest;

use App\Models\User;
use Database\Seeders\TipoUsuarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DestroyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TipoUsuarioSeeder::class);
    }

    public function test_admin_puede_eliminar_usuario(): void
    {
        $admin = User::factory()->administrador()->create();
        $ganadero = User::factory()->ganadero()->create();

        $this->actingAs($admin)
            ->deleteJson("/api/usuarios/{$ganadero->id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('users', ['id' => $ganadero->id]);
    }

    public function test_eliminar_usuario_inexistente_devuelve_404(): void
    {
        $admin = User::factory()->administrador()->create();

        $this->actingAs($admin)
            ->deleteJson('/api/usuarios/9999')
            ->assertStatus(404);
    }
}

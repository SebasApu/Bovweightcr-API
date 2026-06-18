<?php

namespace Tests\Feature\Controllers\UsuarioControllerTest;

use App\Models\User;
use Database\Seeders\TipoUsuarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TipoUsuarioSeeder::class);
    }

    public function test_admin_puede_actualizar_nombre_de_usuario(): void
    {
        $admin = User::factory()->administrador()->create();
        $ganadero = User::factory()->ganadero()->create();

        $this->actingAs($admin)
            ->putJson("/api/usuarios/{$ganadero->id}", ['nombre' => 'Nombre Actualizado'])
            ->assertStatus(200)
            ->assertJsonPath('nombre', 'Nombre Actualizado');
    }

    public function test_actualizar_usuario_con_correo_en_uso_devuelve_409(): void
    {
        $admin = User::factory()->administrador()->create();
        $ganadero = User::factory()->ganadero()->create();
        User::factory()->create(['correo' => 'en-uso@test.com']);

        $this->actingAs($admin)
            ->putJson("/api/usuarios/{$ganadero->id}", ['correo' => 'en-uso@test.com'])
            ->assertStatus(409);
    }
}

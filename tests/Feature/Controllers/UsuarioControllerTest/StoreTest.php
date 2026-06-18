<?php

namespace Tests\Feature\Controllers\UsuarioControllerTest;

use App\Events\UsuarioCreado;
use App\Models\User;
use Database\Seeders\TipoUsuarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TipoUsuarioSeeder::class);
    }

    public function test_admin_puede_crear_usuario_y_dispara_evento(): void
    {
        Event::fake([UsuarioCreado::class]);

        $admin = User::factory()->administrador()->create();

        $response = $this->actingAs($admin)
            ->postJson('/api/usuarios', [
                'nombre' => 'Nuevo Ganadero',
                'correo' => 'nuevo@bovweight.com',
                'contrasena' => 'secreta123',
                'tipo_nombre' => 'Ganadero',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('nombre', 'Nuevo Ganadero');

        $this->assertDatabaseHas('users', ['correo' => 'nuevo@bovweight.com']);
        Event::assertDispatched(UsuarioCreado::class);
    }

    public function test_crear_usuario_con_correo_duplicado_devuelve_409(): void
    {
        $admin = User::factory()->administrador()->create();
        User::factory()->create(['correo' => 'existente@test.com']);

        $this->actingAs($admin)
            ->postJson('/api/usuarios', [
                'nombre' => 'Otro',
                'correo' => 'existente@test.com',
                'contrasena' => 'password123',
                'tipo_nombre' => 'Ganadero',
            ])
            ->assertStatus(409);
    }

    public function test_crear_usuario_sin_tipo_devuelve_422(): void
    {
        $admin = User::factory()->administrador()->create();

        $this->actingAs($admin)
            ->postJson('/api/usuarios', [
                'nombre' => 'Sin Tipo',
                'correo' => 'sintipo@test.com',
                'contrasena' => 'password123',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['tipo_nombre']);
    }

    public function test_crear_usuario_con_tipo_invalido_devuelve_422(): void
    {
        $admin = User::factory()->administrador()->create();

        $this->actingAs($admin)
            ->postJson('/api/usuarios', [
                'nombre' => 'Tipo Malo',
                'correo' => 'tipomalo@test.com',
                'contrasena' => 'password123',
                'tipo_nombre' => 'TipoInexistente',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['tipo_nombre']);
    }
}

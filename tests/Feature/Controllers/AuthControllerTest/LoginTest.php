<?php

namespace Tests\Feature\Controllers\AuthControllerTest;

use App\Models\User;
use Database\Seeders\TipoUsuarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TipoUsuarioSeeder::class);
    }

    public function test_login_exitoso_devuelve_token(): void
    {
        $usuario = User::factory()->create([
            'correo' => 'ganadero@test.com',
            'contrasena' => 'password123',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'correo' => 'ganadero@test.com',
            'contrasena' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token', 'usuario', 'message'])
            ->assertJsonPath('usuario.correo', 'ganadero@test.com');
    }

    public function test_login_con_contrasena_incorrecta_devuelve_401(): void
    {
        User::factory()->create(['correo' => 'test@test.com', 'contrasena' => 'correcta']);

        $response = $this->postJson('/api/auth/login', [
            'correo' => 'test@test.com',
            'contrasena' => 'incorrecta',
        ]);

        $response->assertStatus(401);
    }

    public function test_login_con_correo_inexistente_devuelve_401(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'correo' => 'noexiste@test.com',
            'contrasena' => 'cualquiera',
        ]);

        $response->assertStatus(401);
    }

    public function test_login_sin_correo_devuelve_422(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'contrasena' => 'password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['correo']);
    }

    public function test_login_con_correo_invalido_devuelve_422(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'correo' => 'no-es-un-correo',
            'contrasena' => 'password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['correo']);
    }
}

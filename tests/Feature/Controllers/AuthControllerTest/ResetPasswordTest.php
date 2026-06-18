<?php

namespace Tests\Feature\Controllers\AuthControllerTest;

use App\Models\User;
use Database\Seeders\TipoUsuarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class ResetPasswordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TipoUsuarioSeeder::class);
    }

    public function test_reset_password_con_token_valido_exitoso(): void
    {
        $usuario = User::factory()->create([
            'correo' => 'usuario@test.com',
            'contrasena' => 'passwordOld',
        ]);

        $token = Password::broker()->createToken($usuario);

        $response = $this->postJson('/api/auth/reset-password', [
            'correo' => 'usuario@test.com',
            'token' => $token,
            'contrasena' => 'nuevaContrasena123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['message']);

        // Verificar que la contraseña cambió
        $usuario->refresh();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('nuevaContrasena123', $usuario->contrasena));
    }

    public function test_reset_password_con_token_invalido_devuelve_422(): void
    {
        $usuario = User::factory()->create(['correo' => 'usuario@test.com']);

        $response = $this->postJson('/api/auth/reset-password', [
            'correo' => 'usuario@test.com',
            'token' => 'token-invalido',
            'contrasena' => 'nuevaContrasena123',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['message']);
    }

    public function test_reset_password_sin_campos_requeridos_devuelve_422(): void
    {
        $response = $this->postJson('/api/auth/reset-password', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['correo', 'token', 'contrasena']);
    }
}

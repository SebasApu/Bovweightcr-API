<?php

namespace Tests\Feature\Controllers\AuthControllerTest;

use App\Models\User;
use Database\Seeders\TipoUsuarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TipoUsuarioSeeder::class);
    }

    public function test_forgot_password_con_correo_valido_devuelve_200(): void
    {
        User::factory()->create(['correo' => 'usuario@test.com']);

        $response = $this->postJson('/api/auth/forgot-password', [
            'correo' => 'usuario@test.com',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['message']);
    }

    public function test_forgot_password_con_correo_inexistente_devuelve_200(): void
    {
        // No revelar si el correo existe (seguridad)
        $response = $this->postJson('/api/auth/forgot-password', [
            'correo' => 'noexiste@test.com',
        ]);

        $response->assertStatus(200);
    }

    public function test_forgot_password_sin_correo_devuelve_422(): void
    {
        $this->postJson('/api/auth/forgot-password', [])->assertStatus(422);
    }
}

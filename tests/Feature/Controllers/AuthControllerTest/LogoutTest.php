<?php

namespace Tests\Feature\Controllers\AuthControllerTest;

use App\Models\User;
use Database\Seeders\TipoUsuarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TipoUsuarioSeeder::class);
    }

    public function test_logout_revoca_token(): void
    {
        $usuario = User::factory()->create();
        $token = $usuario->createToken('test')->plainTextToken;

        $response = $this->withToken($token)
            ->postJson('/api/auth/logout');

        $response->assertStatus(200);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_logout_sin_autenticacion_devuelve_401(): void
    {
        $this->postJson('/api/auth/logout')->assertStatus(401);
    }
}

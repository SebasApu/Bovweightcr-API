<?php

namespace Tests\Feature\Controllers\SolicitudRegistroControllerTest;

use App\Models\SolicitudRegistro;
use App\Models\User;
use Database\Seeders\EstadoSolicitudSeeder;
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
        $this->seed(EstadoSolicitudSeeder::class);
    }

    public function test_admin_puede_listar_todas_las_solicitudes(): void
    {
        $admin = User::factory()->administrador()->create();
        SolicitudRegistro::factory()->count(3)->create();

        $this->actingAs($admin)
            ->getJson('/api/solicitudes')
            ->assertStatus(200)
            ->assertJsonCount(3);
    }

    public function test_ganadero_no_puede_listar_solicitudes(): void
    {
        $ganadero = User::factory()->ganadero()->create();

        $this->actingAs($ganadero)
            ->getJson('/api/solicitudes')
            ->assertStatus(403);
    }
}

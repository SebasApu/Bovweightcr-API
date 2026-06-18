<?php

namespace Tests\Feature\Controllers\SolicitudRegistroControllerTest;

use App\Models\SolicitudRegistro;
use App\Models\User;
use Database\Seeders\EstadoSolicitudSeeder;
use Database\Seeders\TipoUsuarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PendientesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TipoUsuarioSeeder::class);
        $this->seed(EstadoSolicitudSeeder::class);
    }

    public function test_admin_puede_listar_solicitudes_pendientes(): void
    {
        $admin = User::factory()->administrador()->create();
        SolicitudRegistro::factory()->count(2)->pendiente()->create();
        SolicitudRegistro::factory()->aprobada()->create();

        $this->actingAs($admin)
            ->getJson('/api/solicitudes/pendientes')
            ->assertStatus(200)
            ->assertJsonCount(2);
    }
}

<?php

namespace Tests\Feature\Controllers\SolicitudRegistroControllerTest;

use App\Models\SolicitudRegistro;
use App\Models\User;
use Database\Seeders\EstadoSolicitudSeeder;
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
        $this->seed(EstadoSolicitudSeeder::class);
    }

    public function test_admin_puede_ver_solicitud_por_id(): void
    {
        $admin = User::factory()->administrador()->create();
        $solicitud = SolicitudRegistro::factory()->create();

        $response = $this->actingAs($admin)
            ->getJson("/api/solicitudes/{$solicitud->id}");

        $response->assertStatus(200)
            ->assertJsonPath('correo', $solicitud->correo);
    }

    public function test_show_solicitud_inexistente_devuelve_404(): void
    {
        $admin = User::factory()->administrador()->create();

        $this->actingAs($admin)
            ->getJson('/api/solicitudes/9999')
            ->assertStatus(404);
    }

    public function test_ganadero_no_puede_ver_solicitud(): void
    {
        $ganadero = User::factory()->ganadero()->create();
        $solicitud = SolicitudRegistro::factory()->create();

        $this->actingAs($ganadero)
            ->getJson("/api/solicitudes/{$solicitud->id}")
            ->assertStatus(403);
    }
}

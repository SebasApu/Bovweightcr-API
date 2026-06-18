<?php

namespace Tests\Feature\Controllers\SolicitudRegistroControllerTest;

use App\Events\SolicitudAprobada;
use App\Events\SolicitudRechazada;
use App\Models\EstadoSolicitud;
use App\Models\SolicitudRegistro;
use App\Models\User;
use Database\Seeders\EstadoSolicitudSeeder;
use Database\Seeders\TipoUsuarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RevisarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TipoUsuarioSeeder::class);
        $this->seed(EstadoSolicitudSeeder::class);
    }

    public function test_admin_puede_aprobar_solicitud_y_dispara_evento(): void
    {
        Event::fake([SolicitudAprobada::class]);

        $admin = User::factory()->administrador()->create();
        $solicitud = SolicitudRegistro::factory()->pendiente()->create();

        $response = $this->actingAs($admin)
            ->putJson("/api/solicitudes/{$solicitud->id}/revisar", [
                'decision' => 'aprobar',
                'tipo_usuario' => 'Ganadero',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('estado', 'Aprobado');

        $this->assertDatabaseHas('solicitud_registros', [
            'id' => $solicitud->id,
            'estado_id' => EstadoSolicitud::where('nombre', 'Aprobado')->first()->id,
        ]);

        $this->assertDatabaseHas('users', ['correo' => $solicitud->correo]);

        Event::assertDispatched(SolicitudAprobada::class);
    }

    public function test_admin_puede_rechazar_solicitud_con_motivo_y_dispara_evento(): void
    {
        Event::fake([SolicitudRechazada::class]);

        $admin = User::factory()->administrador()->create();
        $solicitud = SolicitudRegistro::factory()->pendiente()->create();

        $response = $this->actingAs($admin)
            ->putJson("/api/solicitudes/{$solicitud->id}/revisar", [
                'decision' => 'rechazar',
                'motivo' => 'Documentación incompleta.',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('estado', 'Rechazado')
            ->assertJsonPath('motivo_rechazo', 'Documentación incompleta.');

        Event::assertDispatched(SolicitudRechazada::class);
    }

    public function test_revisar_solicitud_ya_revisada_devuelve_422(): void
    {
        $admin = User::factory()->administrador()->create();
        $solicitud = SolicitudRegistro::factory()->aprobada()->create();

        $this->actingAs($admin)
            ->putJson("/api/solicitudes/{$solicitud->id}/revisar", [
                'decision' => 'aprobar',
                'tipo_usuario' => 'Ganadero',
            ])
            ->assertStatus(422);
    }

    public function test_rechazar_sin_motivo_devuelve_422(): void
    {
        $admin = User::factory()->administrador()->create();
        $solicitud = SolicitudRegistro::factory()->pendiente()->create();

        $this->actingAs($admin)
            ->putJson("/api/solicitudes/{$solicitud->id}/revisar", [
                'decision' => 'rechazar',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['motivo']);
    }

    public function test_revisar_solicitud_inexistente_devuelve_404(): void
    {
        $admin = User::factory()->administrador()->create();

        $this->actingAs($admin)
            ->putJson('/api/solicitudes/9999/revisar', [
                'decision' => 'aprobar',
                'tipo_usuario' => 'Ganadero',
            ])
            ->assertStatus(404);
    }
}

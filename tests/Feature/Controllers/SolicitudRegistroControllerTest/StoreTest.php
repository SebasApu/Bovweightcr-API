<?php

namespace Tests\Feature\Controllers\SolicitudRegistroControllerTest;

use App\Models\User;
use Database\Seeders\EstadoSolicitudSeeder;
use Database\Seeders\TipoUsuarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TipoUsuarioSeeder::class);
        $this->seed(EstadoSolicitudSeeder::class);
    }

    private function datosSolicitud(array $override = []): array
    {
        return array_merge([
            'nombre' => 'Carlos',
            'apellidos' => 'Méndez Arias',
            'correo' => 'carlos@ganadero.com',
            'numero_celular' => '88001234',
        ], $override);
    }

    public function test_usuario_externo_puede_enviar_solicitud(): void
    {
        $response = $this->postJson('/api/solicitudes', $this->datosSolicitud());

        $response->assertStatus(201)
            ->assertJsonPath('correo', 'carlos@ganadero.com')
            ->assertJsonPath('estado', 'Pendiente');

        $this->assertDatabaseHas('solicitud_registros', ['correo' => 'carlos@ganadero.com']);
    }

    public function test_solicitud_sin_nombre_devuelve_422(): void
    {
        $this->postJson('/api/solicitudes', $this->datosSolicitud(['nombre' => '']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['nombre']);
    }

    public function test_solicitud_con_correo_invalido_devuelve_422(): void
    {
        $this->postJson('/api/solicitudes', $this->datosSolicitud(['correo' => 'no-es-correo']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['correo']);
    }

    public function test_solicitud_duplicada_devuelve_409(): void
    {
        $this->postJson('/api/solicitudes', $this->datosSolicitud());
        $this->postJson('/api/solicitudes', $this->datosSolicitud())
            ->assertStatus(409);
    }

    public function test_solicitud_con_correo_ya_registrado_devuelve_409(): void
    {
        User::factory()->create(['correo' => 'carlos@ganadero.com']);

        $this->postJson('/api/solicitudes', $this->datosSolicitud())
            ->assertStatus(409);
    }
}

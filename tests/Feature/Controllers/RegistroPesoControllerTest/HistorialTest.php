<?php

namespace Tests\Feature\Controllers\RegistroPesoControllerTest;

use App\Models\EstadoComercialGanado;
use App\Models\EstadoSaludGanado;
use App\Models\Finca;
use App\Models\Ganado;
use App\Models\RegistroPeso;
use App\Models\User;
use Database\Seeders\EstadoComercialGanadoSeeder;
use Database\Seeders\EstadoSaludGanadoSeeder;
use Database\Seeders\TipoUsuarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HistorialTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TipoUsuarioSeeder::class);
        $this->seed(EstadoSaludGanadoSeeder::class);
        $this->seed(EstadoComercialGanadoSeeder::class);
    }

    public function test_usuario_puede_obtener_historial_de_pesos_de_su_ganado(): void
    {
        $usuario = User::factory()->ganadero()->create();
        $finca = Finca::create([
            'usuario_id' => $usuario->id,
            'nombre' => 'Finca Central',
            'ubicacion' => 'Puntarenas',
            'area' => 75.0,
            'numero_finca' => 'CR-5001',
        ]);

        $salud = EstadoSaludGanado::first();
        $comercial = EstadoComercialGanado::first();

        $ganado = Ganado::create([
            'finca_id' => $finca->id,
            'estado_salud_id' => $salud->id,
            'estado_comercial_id' => $comercial->id,
            'arete' => 'ART-101',
            'nombre' => 'Pinta',
            'sexo' => 'Hembra',
            'raza' => 'Holstein',
        ]);

        // Registrar algunos pesos
        RegistroPeso::create([
            'ganado_id' => $ganado->id,
            'peso_estimado' => 400.0,
            'peso_corregido' => 400.0,
            'fecha' => now()->subDays(10),
            'metodo' => 'manual',
        ]);
        RegistroPeso::create([
            'ganado_id' => $ganado->id,
            'peso_estimado' => 420.0,
            'peso_corregido' => 420.0,
            'fecha' => now(),
            'metodo' => 'manual',
        ]);

        $response = $this->actingAs($usuario)
            ->getJson("/api/ganado/{$ganado->id}/historial");

        $response->assertStatus(200)
            ->assertJsonCount(2);

        $data = $response->json();
        $this->assertEquals(420.0, (float) $data[0]['peso_estimado']);
        $this->assertEquals(400.0, (float) $data[1]['peso_estimado']);
    }

    public function test_obtener_historial_de_ganado_inexistente_devuelve_404(): void
    {
        $usuario = User::factory()->ganadero()->create();

        $response = $this->actingAs($usuario)
            ->getJson('/api/ganado/9999/historial');

        $response->assertStatus(404);
    }
}

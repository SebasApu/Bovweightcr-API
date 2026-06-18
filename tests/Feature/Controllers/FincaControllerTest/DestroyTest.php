<?php

namespace Tests\Feature\Controllers\FincaControllerTest;

use App\Models\EstadoComercialGanado;
use App\Models\EstadoSaludGanado;
use App\Models\Finca;
use App\Models\Ganado;
use App\Models\User;
use Database\Seeders\EstadoComercialGanadoSeeder;
use Database\Seeders\EstadoSaludGanadoSeeder;
use Database\Seeders\TipoUsuarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DestroyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TipoUsuarioSeeder::class);
        $this->seed(EstadoSaludGanadoSeeder::class);
        $this->seed(EstadoComercialGanadoSeeder::class);
    }

    public function test_usuario_puede_eliminar_finca(): void
    {
        $usuario = User::factory()->ganadero()->create();
        $finca = Finca::create([
            'usuario_id' => $usuario->id,
            'nombre' => 'Finca Temporal',
            'ubicacion' => 'Puntarenas',
            'area' => 75.0,
            'numero_finca' => 'CR-TEMP',
        ]);

        $response = $this->actingAs($usuario)
            ->deleteJson("/api/fincas/{$finca->id}");

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Finca eliminada correctamente');

        $this->assertDatabaseMissing('fincas', ['id' => $finca->id]);
    }

    public function test_eliminar_finca_con_ganado_asociado_devuelve_400(): void
    {
        $usuario = User::factory()->ganadero()->create();
        $finca = Finca::create([
            'usuario_id' => $usuario->id,
            'nombre' => 'Finca Con Animales',
            'ubicacion' => 'Puntarenas',
            'area' => 75.0,
            'numero_finca' => 'CR-CON-ANIMALES',
        ]);

        $salud = EstadoSaludGanado::first();
        $comercial = EstadoComercialGanado::first();

        // Registrar ganado asociado
        Ganado::create([
            'finca_id' => $finca->id,
            'estado_salud_id' => $salud->id,
            'estado_comercial_id' => $comercial->id,
            'arete' => 'ART-100',
            'nombre' => 'Toro 1',
            'sexo' => 'Macho',
            'raza' => 'Brahman',
        ]);

        $response = $this->actingAs($usuario)
            ->deleteJson("/api/fincas/{$finca->id}");

        $response->assertStatus(400)
            ->assertJsonPath('message', 'No se puede eliminar la finca porque tiene ganado asociado');

        $this->assertDatabaseHas('fincas', ['id' => $finca->id]);
    }

    public function test_eliminar_finca_inexistente_devuelve_404(): void
    {
        $usuario = User::factory()->ganadero()->create();

        $response = $this->actingAs($usuario)
            ->deleteJson('/api/fincas/9999');

        $response->assertStatus(404);
    }
}

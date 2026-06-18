<?php

namespace Tests\Feature\Controllers\GanadoControllerTest;

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

class ActualizarEstadoSaludTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TipoUsuarioSeeder::class);
        $this->seed(EstadoSaludGanadoSeeder::class);
        $this->seed(EstadoComercialGanadoSeeder::class);
    }

    private function crearFincaYGanado(int $duenoId, ?int $veterinarioId = null): Ganado
    {
        $finca = Finca::create([
            'usuario_id' => $duenoId,
            'veterinario_id' => $veterinarioId,
            'nombre' => 'Finca Central',
            'ubicacion' => 'Puntarenas',
            'area' => 75.0,
            'numero_finca' => 'CR-5001',
        ]);

        $salud = EstadoSaludGanado::first();
        $comercial = EstadoComercialGanado::first();

        return Ganado::create([
            'finca_id' => $finca->id,
            'estado_salud_id' => $salud->id,
            'estado_comercial_id' => $comercial->id,
            'arete' => 'ART-0001',
            'nombre' => 'Vaca 1',
            'sexo' => 'Hembra',
            'raza' => 'Holstein',
        ]);
    }

    public function test_veterinario_asignado_puede_actualizar_estado_salud(): void
    {
        $ganadero = User::factory()->ganadero()->create();
        $veterinario = User::factory()->veterinario()->create();
        $ganado = $this->crearFincaYGanado($ganadero->id, $veterinario->id);
        $nuevoEstado = EstadoSaludGanado::where('id', '!=', $ganado->estado_salud_id)->first()
            ?? EstadoSaludGanado::create(['nombre' => 'En tratamiento']);

        $response = $this->actingAs($veterinario)
            ->patchJson("/api/ganado/{$ganado->id}/estado-salud", [
                'estado_salud_id' => $nuevoEstado->id,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.estado_salud_id', $nuevoEstado->id);
    }

    public function test_veterinario_no_asignado_recibe_403(): void
    {
        $ganadero = User::factory()->ganadero()->create();
        $veterinarioAsignado = User::factory()->veterinario()->create();
        $otroVeterinario = User::factory()->veterinario()->create();
        $ganado = $this->crearFincaYGanado($ganadero->id, $veterinarioAsignado->id);
        $estado = EstadoSaludGanado::first();

        $response = $this->actingAs($otroVeterinario)
            ->patchJson("/api/ganado/{$ganado->id}/estado-salud", [
                'estado_salud_id' => $estado->id,
            ]);

        $response->assertStatus(403);
    }

    public function test_ganadero_dueno_no_puede_usar_este_endpoint(): void
    {
        $ganadero = User::factory()->ganadero()->create();
        $veterinario = User::factory()->veterinario()->create();
        $ganado = $this->crearFincaYGanado($ganadero->id, $veterinario->id);
        $estado = EstadoSaludGanado::first();

        $response = $this->actingAs($ganadero)
            ->patchJson("/api/ganado/{$ganado->id}/estado-salud", [
                'estado_salud_id' => $estado->id,
            ]);

        $response->assertStatus(403);
    }

    public function test_actualizar_estado_salud_animal_inexistente_devuelve_404(): void
    {
        $veterinario = User::factory()->veterinario()->create();
        $estado = EstadoSaludGanado::first();

        $response = $this->actingAs($veterinario)
            ->patchJson('/api/ganado/9999/estado-salud', [
                'estado_salud_id' => $estado->id,
            ]);

        $response->assertStatus(404);
    }

    public function test_estado_salud_id_invalido_devuelve_422(): void
    {
        $ganadero = User::factory()->ganadero()->create();
        $veterinario = User::factory()->veterinario()->create();
        $ganado = $this->crearFincaYGanado($ganadero->id, $veterinario->id);

        $response = $this->actingAs($veterinario)
            ->patchJson("/api/ganado/{$ganado->id}/estado-salud", [
                'estado_salud_id' => 999999,
            ]);

        $response->assertStatus(422);
    }
}

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

class RegistrarPesoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TipoUsuarioSeeder::class);
        $this->seed(EstadoSaludGanadoSeeder::class);
        $this->seed(EstadoComercialGanadoSeeder::class);
    }

    public function test_usuario_puede_registrar_peso_para_su_ganado(): void
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

        $response = $this->actingAs($usuario)
            ->postJson("/api/ganado/{$ganado->id}/peso", [
                'peso' => 450.5,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Peso registrado correctamente');

        $this->assertDatabaseHas('registro_pesos', [
            'ganado_id' => $ganado->id,
            'peso_estimado' => 450.5,
            'peso_corregido' => 450.5,
            'metodo' => 'manual',
        ]);
    }

    public function test_usuario_no_puede_registrar_peso_para_ganado_ajeno(): void
    {
        $ganadero1 = User::factory()->ganadero()->create();
        $ganadero2 = User::factory()->ganadero()->create();

        $finca = Finca::create([
            'usuario_id' => $ganadero2->id,
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

        $response = $this->actingAs($ganadero1)
            ->postJson("/api/ganado/{$ganado->id}/peso", [
                'peso' => 450.5,
            ]);

        $response->assertStatus(403);
    }

    public function test_registrar_peso_invalido_devuelve_422(): void
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

        $response = $this->actingAs($usuario)
            ->postJson("/api/ganado/{$ganado->id}/peso", [
                'peso' => -50, // Peso negativo
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['peso']);
    }

    public function test_registrar_peso_para_ganado_inexistente_devuelve_404(): void
    {
        $usuario = User::factory()->ganadero()->create();

        $response = $this->actingAs($usuario)
            ->postJson('/api/ganado/9999/peso', [
                'peso' => 450.5,
            ]);

        $response->assertStatus(404);
    }
}

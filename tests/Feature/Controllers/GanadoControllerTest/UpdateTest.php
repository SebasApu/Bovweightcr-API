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

class UpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TipoUsuarioSeeder::class);
        $this->seed(EstadoSaludGanadoSeeder::class);
        $this->seed(EstadoComercialGanadoSeeder::class);
    }

    public function test_usuario_puede_actualizar_ganado(): void
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
            'arete' => 'ART-ORIGINAL',
            'nombre' => 'Nombre original',
            'sexo' => 'Hembra',
            'raza' => 'Holstein',
        ]);

        $response = $this->actingAs($usuario)
            ->putJson("/api/ganado/{$ganado->id}", [
                'arete' => 'ART-MODIFICADO',
                'nombre' => 'Nombre modificado',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.arete', 'ART-MODIFICADO')
            ->assertJsonPath('data.nombre', 'Nombre modificado');
    }

    public function test_actualizar_ganado_con_arete_duplicado_devuelve_409(): void
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

        $ganado1 = Ganado::create([
            'finca_id' => $finca->id,
            'estado_salud_id' => $salud->id,
            'estado_comercial_id' => $comercial->id,
            'arete' => 'ART-UNO',
            'nombre' => 'Toro Uno',
            'sexo' => 'Macho',
            'raza' => 'Brahman',
        ]);

        $ganado2 = Ganado::create([
            'finca_id' => $finca->id,
            'estado_salud_id' => $salud->id,
            'estado_comercial_id' => $comercial->id,
            'arete' => 'ART-DOS',
            'nombre' => 'Toro Dos',
            'sexo' => 'Macho',
            'raza' => 'Brahman',
        ]);

        $response = $this->actingAs($usuario)
            ->putJson("/api/ganado/{$ganado2->id}", [
                'arete' => 'ART-UNO', // Duplicado de ganado1
            ]);

        $response->assertStatus(409);
    }

    public function test_actualizar_ganado_inexistente_devuelve_404(): void
    {
        $usuario = User::factory()->ganadero()->create();

        $response = $this->actingAs($usuario)
            ->putJson('/api/ganado/9999', [
                'arete' => 'ART-MODIFICADO',
            ]);

        $response->assertStatus(404);
    }
}

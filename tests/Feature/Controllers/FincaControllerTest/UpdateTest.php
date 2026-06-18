<?php

namespace Tests\Feature\Controllers\FincaControllerTest;

use App\Models\Finca;
use App\Models\User;
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
    }

    public function test_usuario_puede_actualizar_finca(): void
    {
        $usuario = User::factory()->ganadero()->create();
        $finca = Finca::create([
            'usuario_id' => $usuario->id,
            'nombre' => 'Finca Original',
            'ubicacion' => 'Puntarenas',
            'area' => 75.0,
            'numero_finca' => 'CR-ORIGINAL',
        ]);

        $response = $this->actingAs($usuario)
            ->putJson("/api/fincas/{$finca->id}", [
                'usuario_id' => $usuario->id,
                'nombre' => 'Finca Modificada',
                'ubicacion' => 'San José',
                'area' => 80.0,
                'numero_finca' => 'CR-MODIFICADO',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.nombre', 'Finca Modificada')
            ->assertJsonPath('data.numero_finca', 'CR-MODIFICADO');
    }

    public function test_actualizar_finca_con_numero_duplicado_devuelve_422(): void
    {
        $usuario = User::factory()->ganadero()->create();
        $finca1 = Finca::create([
            'usuario_id' => $usuario->id,
            'nombre' => 'Finca Uno',
            'ubicacion' => 'Cartago',
            'area' => 30.0,
            'numero_finca' => 'CR-UNO',
        ]);
        $finca2 = Finca::create([
            'usuario_id' => $usuario->id,
            'nombre' => 'Finca Dos',
            'ubicacion' => 'Cartago',
            'area' => 40.0,
            'numero_finca' => 'CR-DOS',
        ]);

        $response = $this->actingAs($usuario)
            ->putJson("/api/fincas/{$finca2->id}", [
                'usuario_id' => $usuario->id,
                'nombre' => 'Finca Dos Modificada',
                'ubicacion' => 'Cartago',
                'area' => 45.0,
                'numero_finca' => 'CR-UNO', // Duplicado de Finca Uno
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['numero_finca']);
    }

    public function test_actualizar_finca_inexistente_devuelve_404(): void
    {
        $usuario = User::factory()->ganadero()->create();

        $response = $this->actingAs($usuario)
            ->putJson('/api/fincas/9999', [
                'usuario_id' => $usuario->id,
                'nombre' => 'Finca Modificada',
                'ubicacion' => 'San José',
                'area' => 80.0,
                'numero_finca' => 'CR-MODIFICADO',
            ]);

        $response->assertStatus(404);
    }
}

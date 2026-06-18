<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function seed($class = 'Database\\Seeders\\DatabaseSeeder')
    {
        if ($class === 'Database\\Seeders\\EstadoComercialGanadoSeeder' || $class === \Database\Seeders\EstadoComercialGanadoSeeder::class) {
            $estados = ['Disponible', 'Reservado', 'En negociación', 'Vendido', 'No disponible'];
            foreach ($estados as $estado) {
                \App\Models\EstadoComercialGanado::firstOrCreate(['nombre' => $estado]);
            }
            return $this;
        }

        return parent::seed($class);
    }
}

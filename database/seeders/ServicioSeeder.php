<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServicioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $servicios = [
            [
                'Nombre' => 'Corte Clasico',
                'Duracion' => 30,
                'Precio' => 20000,
            ],
            [
                'Nombre' => 'Perfilado de Barba',
                'Duracion' => 20,
                'Precio' => 15000,
            ],
            [
                'Nombre' => 'Corte y Barba Premium',
                'Duracion' => 60,
                'Precio' => 35000,
            ],
            [
                'Nombre' => 'Lavado y peinado',
                'Duracion' => 30,
                'Precio' => 18000,
            ],
        ];

        foreach ($servicios as $servicio) {
            DB::table('servicio')->updateOrInsert(
                ['Nombre' => $servicio['Nombre']],
                $servicio,
            );
        }
    }
}

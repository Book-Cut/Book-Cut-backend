<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ServicioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        \DB::table('servicio')->insert([
            [
                'idServicio' => 1,
                'Nombre' => 'Corte Clasico',
                'Duracion' => '30',
                'Precio' => 20000.00,
            ],
            [
                'idServicio' => 2,
                'Nombre' => 'Perfilado de Barba',
                'Duracion' => '20',
                'Precio' => 15000.00,
            ],
            [
                'idServicio' => 3,
                'Nombre' => 'Corte y Barba Premium',
                'Duracion' => '60',
                'Precio' => 35000.00,
            ],
        ]);
    }
}

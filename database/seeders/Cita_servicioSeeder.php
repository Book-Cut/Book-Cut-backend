<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class Cita_servicioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        \DB::table('cita_servicio')->insert([
            [
                'idCita' => 1,
                'idServicio' => 3,
                'fecha_hora_servicio' => null,

            ],
            [
                'idCita' => 1,
                'idServicio' => 1,
                'fecha_hora_servicio' => null,
            ],
            [
                'idCita' => 2,
                'idServicio' => 2,
                'fecha_hora_servicio' => null,

            ],
            [
                'idCita' => 3,
                'idServicio' => 3,
                'fecha_hora_servicio' => null,

            ],
        ]);
    }
}

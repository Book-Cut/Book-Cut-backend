<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ValoraSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        \DB::table('valora')->insert([
            [
                'EvaluacionBar' => 5,
                'EvaluacionCita' => 4,
                'Fecha_evaluacion_bar' => '2026-07-20 10:00:00',
                'Fecha_evaluacion_cita' => '2026-07-20 10:00:00',
            ],
            [
                'EvaluacionBar' => 5,
                'EvaluacionCita' => 5,
                'Fecha_evaluacion_bar' => '2026-07-21 10:00:00',
                'Fecha_evaluacion_cita' => '2026-07-21 10:00:00',
            ],
            [
                'EvaluacionBar' => 4,
                'EvaluacionCita' => 3,
                'Fecha_evaluacion_bar' => '2026-07-22 10:00:00',
                'Fecha_evaluacion_cita' => '2026-07-22 10:00:00',
            ],
        ]);
    }
}

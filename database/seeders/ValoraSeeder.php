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
                'Evaluacion_(Barbero)_idEvaluacion_Bar' => 5,
                'Evaluacion_(Cliente)_idEvaluacion_cli' => 4,
                'Fecha_evaluacion_bar' => '2026-07-20',
                'Fecha_evaluacion_cli' => '2026-07-20',
            ],
            [
                'Evaluacion_(Barbero)_idEvaluacion_Bar' => 5,
                'Evaluacion_(Cliente)_idEvaluacion_cli' => 5,
                'Fecha_evaluacion_bar' => '2026-07-21',
                'Fecha_evaluacion_cli' => '2026-07-21',
            ],
            [
                'Evaluacion_(Barbero)_idEvaluacion_Bar' => 4,
                'Evaluacion_(Cliente)_idEvaluacion_cli' => 3,
                'Fecha_evaluacion_bar' => '2026-07-22',
                'Fecha_evaluacion_cli' => '2026-07-22',
            ],
        ]);
    }
}

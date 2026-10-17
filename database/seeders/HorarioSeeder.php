<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HorarioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $horarios = [
            [
                'diasemana' => 'Lunes',
                'horainicio' => '09:00:00',
                'horafin' => '19:00:00',
            ],
            [
                'diasemana' => 'Martes',
                'horainicio' => '09:00:00',
                'horafin' => '19:00:00',
            ],
            [
                'diasemana' => 'Miércoles',
                'horainicio' => '09:00:00',
                'horafin' => '19:00:00',
            ],
            [
                'diasemana' => 'Jueves',
                'horainicio' => '09:00:00',
                'horafin' => '19:00:00',
            ],
            [
                'diasemana' => 'Viernes',
                'horainicio' => '09:00:00',
                'horafin' => '19:00:00',
            ],
            [
                'diasemana' => 'Sábado',
                'horainicio' => '10:00:00',
                'horafin' => '20:00:00',
            ],

        ];

        foreach ($horarios as $horario) {
            DB::table('horario')->updateOrInsert(
                ['diasemana' => $horario['diasemana']],
                $horario,
            );
        }
    }
}

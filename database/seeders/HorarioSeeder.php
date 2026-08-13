<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class HorarioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        \DB::table('horario')->insert([
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

        ]);
    }
}

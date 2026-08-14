<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CitaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        \DB::table('cita')->insert([
            [
                'idCita' => 1,
                'fecha_hora' => '2026-07-20 10:00:0',
                'estado' => 'Confirmado',
                'Valora_Idvalora' => 1,
                'Usuario_idUsuarioCli' => 4,
                'Usuario_idUsuarioBar' => 2,
            ],
            [
                'idCita' => 2,
                'fecha_hora' => '2026-07-21 11:00:0',
                'estado' => 'Confirmado',
                'Valora_Idvalora' => 2,
                'Usuario_idUsuarioCli' => 5,
                'Usuario_idUsuarioBar' => 3,
            ],
            [
                'idCita' => 3,
                'fecha_hora' => '2026-07-22 12:00:0',
                'estado' => 'Pendiente',
                'Valora_Idvalora' => 3,
                'Usuario_idUsuarioCli' => 4,
                'Usuario_idUsuarioBar' => 3,
            ],
        ]);

    }
}

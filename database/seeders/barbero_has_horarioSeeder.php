<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class barbero_has_horarioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $horarios = DB::table('horario')->pluck('idhorario', 'diasemana');
        $barberos = DB::table('usuario')->whereIn('correo', ['carlos@bookcut.com', 'andres@bookcut.com'])->pluck('idUsuario', 'correo');

        foreach ([
            'carlos@bookcut.com' => ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'],
            'andres@bookcut.com' => ['Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'],
        ] as $correo => $dias) {
            foreach ($dias as $dia) {
                DB::table('barbero_has_horario')->updateOrInsert(
                    [
                        'horario_idhorario' => $horarios[$dia],
                        'Usuario_idUsuarioBar' => $barberos[$correo],
                    ],
                    [
                        'horario_idhorario' => $horarios[$dia],
                        'Usuario_idUsuarioBar' => $barberos[$correo],
                    ],
                );
            }
        }
    }
}

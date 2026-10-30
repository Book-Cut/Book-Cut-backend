<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class notificacionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $citas = DB::table('cita')
            ->join('Usuario', 'Usuario.idUsuario', '=', 'cita.Usuario_idUsuarioCli')
            ->whereIn('estado', ['Confirmado', 'Pendiente'])
            ->whereIn('Usuario.correo', ['luis@gmail.com', 'miguel@gmail.com', 'sofia@gmail.com'])
            ->get(['cita.idCita', 'cita.estado']);

        foreach ($citas as $cita) {
            $mensaje = $cita->estado === 'Confirmado'
                ? 'Tu cita está confirmada.'
                : 'Tu cita está pendiente de confirmación.';

            DB::table('notificacion')->updateOrInsert(
                ['Cita_idCita' => $cita->idCita, 'tipo' => 'Correo'],
                [
                    'tipo' => 'Correo',
                    'mensaje' => $mensaje,
                    'fecha_envio' => now()->toDateString(),
                    'Cita_idCita' => $cita->idCita,
                ],
            );
        }
    }
}

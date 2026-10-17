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
            ->join('factura', 'factura.Cita_idCita', '=', 'cita.idCita')
            ->whereIn('estado', ['Confirmado', 'Pendiente'])
            ->where('factura.numero_factura', 'like', 'SEED-BOOKCUT-%')
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

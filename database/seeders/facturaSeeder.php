<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class facturaSeeder extends Seeder
{
    public function run(): void
    {
        $citas = DB::table('cita')
            ->join('factura', 'factura.Cita_idCita', '=', 'cita.idCita')
            ->whereBetween('factura.numero_factura', ['FAC-001', 'FAC-006'])
            ->select(
                'cita.idCita',
                'cita.Fecha_hora',
                'cita.Usuario_idUsuarioCli',
                'factura.numero_factura',
            )
            ->get();

        foreach ($citas as $cita) {
            $subtotal = DB::table('cita_servicio')
                ->join('servicio', 'servicio.idServicio', '=', 'cita_servicio.idServicio')
                ->where('cita_servicio.idCita', $cita->idCita)
                ->sum('servicio.Precio');
            DB::table('factura')
                ->where('numero_factura', $cita->numero_factura)
                ->update([
                    'fecha_emision' => $cita->Fecha_hora,
                    'subtotal' => $subtotal,
                    'total_pagar' => $subtotal,
                    'estado_factura' => 'Pagada',
                    'Cita_idCita' => $cita->idCita,
                    'Usuario_idUsuario' => $cita->Usuario_idUsuarioCli,
                ]);
        }
    }
}

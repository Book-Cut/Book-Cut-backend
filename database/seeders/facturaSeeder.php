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
            ->where('factura.numero_factura', 'like', 'SEED-BOOKCUT-%')
            ->select(
                'cita.idCita',
                'cita.Fecha_hora',
                'cita.estado',
                'cita.Usuario_idUsuarioCli',
                'factura.numero_factura',
            )
            ->get();

        foreach ($citas as $cita) {
            $subtotal = DB::table('cita_servicio')
                ->join('servicio', 'servicio.idServicio', '=', 'cita_servicio.idServicio')
                ->where('cita_servicio.idCita', $cita->idCita)
                ->sum('servicio.Precio');
            $estadoFactura = match ($cita->estado) {
                'Cancelado' => 'Anulada',
                'Finalizado' => 'Pagada',
                default => 'Emitida',
            };

            DB::table('factura')
                ->where('numero_factura', $cita->numero_factura)
                ->update([
                    'fecha_emision' => $cita->Fecha_hora,
                    'subtotal' => $subtotal,
                    'total_pagar' => $subtotal,
                    'metodo_pago' => $cita->estado === 'Finalizado' ? 'Nequi' : 'Pendiente_Pago',
                    'estado_factura' => $estadoFactura,
                    'Cita_idCita' => $cita->idCita,
                    'Usuario_idUsuario' => $cita->Usuario_idUsuarioCli,
                ]);
        }
    }
}

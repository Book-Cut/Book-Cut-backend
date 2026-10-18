<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ValoraSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $citasFinalizadas = DB::table('cita')
            ->join('factura', 'factura.Cita_idCita', '=', 'cita.idCita')
            ->where('cita.estado', 'Finalizado')
            ->whereBetween('factura.numero_factura', ['FAC-001', 'FAC-006'])
            ->select('cita.idCita', 'cita.Fecha_hora', 'cita.Valora_Idvalora')
            ->get();

        foreach ($citasFinalizadas as $cita) {
            $datosValoracion = [
                'EvaluacionBar' => 5,
                'EvaluacionCita' => 5,
                'Fecha_evaluacion_bar' => $cita->Fecha_hora,
                'Fecha_evaluacion_cita' => $cita->Fecha_hora,
            ];

            if ($cita->Valora_Idvalora) {
                DB::table('valora')
                    ->where('Idvalora', $cita->Valora_Idvalora)
                    ->update($datosValoracion);

                continue;
            }

            $idValoracion = DB::table('valora')->insertGetId($datosValoracion, 'Idvalora');

            DB::table('cita')
                ->where('idCita', $cita->idCita)
                ->update(['Valora_Idvalora' => $idValoracion]);
        }
    }
}

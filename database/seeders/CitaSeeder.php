<?php

namespace Database\Seeders;

use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CitaSeeder extends Seeder
{
    public function run(): void
    {
        $usuarios = DB::table('Usuario')->pluck('idUsuario', 'correo');
        $servicios = DB::table('servicio')->get()->keyBy('Nombre');
        $proximaFecha = static function (array $diasPermitidos, int $diasDesdeHoy): CarbonImmutable {
            $fecha = CarbonImmutable::now()->addDays($diasDesdeHoy)->startOfDay();

            while (!in_array($fecha->dayOfWeekIso, $diasPermitidos, true)) {
                $fecha = $fecha->addDay();
            }

            return $fecha;
        };

        $fechaCarlos = $proximaFecha([1, 2, 3, 4, 5], 2);
        $fechaAndres = $proximaFecha([2, 3, 4, 5, 6], 3);
        $citas = [
            [
                'codigo' => 'PROXIMA-LUIS',
                'numeroFactura' => 'FAC-001',
                'fecha' => $fechaCarlos->setTime(9, 0),
                'estado' => 'Confirmado',
                'estadoPago' => 'Pagado',
                'modalidadPago' => 'En línea',
                'metodoPago' => 'Tarjeta',
                'cliente' => 'luis@gmail.com',
                'barbero' => 'carlos@bookcut.com',
                'servicios' => ['Corte y Barba Premium'],
            ],
            [
                'codigo' => 'PENDIENTE-MIGUEL',
                'numeroFactura' => 'FAC-002',
                'fecha' => $fechaCarlos->setTime(11, 0),
                'estado' => 'Pendiente',
                'estadoPago' => 'Pendiente',
                'modalidadPago' => 'Presencial',
                'cliente' => 'miguel@gmail.com',
                'barbero' => 'carlos@bookcut.com',
                'servicios' => ['Corte Clasico', 'Perfilado de Barba'],
            ],
            [
                'codigo' => 'PROXIMA-SOFIA',
                'numeroFactura' => 'FAC-003',
                'fecha' => $fechaAndres->setTime(10, 0),
                'estado' => 'Confirmado',
                'estadoPago' => 'Pendiente',
                'modalidadPago' => 'Presencial',
                'cliente' => 'sofia@gmail.com',
                'barbero' => 'andres@bookcut.com',
                'servicios' => ['Perfilado de Barba'],
            ],
            [
                'codigo' => 'CANCELADA-FUTURA',
                'numeroFactura' => 'FAC-004',
                'fecha' => $fechaCarlos->setTime(9, 0),
                'estado' => 'Cancelado',
                'estadoPago' => 'Pendiente',
                'modalidadPago' => 'Presencial',
                'cliente' => 'sofia@gmail.com',
                'barbero' => 'carlos@bookcut.com',
                'servicios' => ['Corte Clasico'],
            ],
            [
                'codigo' => 'FINALIZADA-LUIS',
                'numeroFactura' => 'FAC-005',
                'fecha' => CarbonImmutable::now()->subDays(10)->setTime(14, 0),
                'estado' => 'Finalizado',
                'estadoPago' => 'Pagado',
                'modalidadPago' => 'Presencial',
                'metodoPago' => 'Efectivo',
                'cliente' => 'luis@gmail.com',
                'barbero' => 'andres@bookcut.com',
                'servicios' => ['Corte Clasico'],
            ],
            [
                'codigo' => 'CANCELADA-MIGUEL',
                'numeroFactura' => 'FAC-006',
                'fecha' => CarbonImmutable::now()->subDays(5)->setTime(16, 0),
                'estado' => 'Cancelado',
                'estadoPago' => 'Pendiente',
                'modalidadPago' => 'Presencial',
                'cliente' => 'miguel@gmail.com',
                'barbero' => 'carlos@bookcut.com',
                'servicios' => ['Perfilado de Barba'],
            ],
        ];

        foreach ($citas as $datosCita) {
            $fechaHora = $datosCita['fecha']->format('Y-m-d H:i:s');
            $idCliente = $usuarios[$datosCita['cliente']];
            $idBarbero = $usuarios[$datosCita['barbero']];
            $numeroFactura = $datosCita['numeroFactura'];
            $facturaExistente = DB::table('factura')
                ->where('numero_factura', $numeroFactura)
                ->first();

            if (!$facturaExistente) {
                $facturaExistente = DB::table('factura')
                    ->where('numero_factura', 'SEED-BOOKCUT-' . $datosCita['codigo'])
                    ->first();
            }

            $citaExistente = DB::table('cita')
                ->where('Fecha_hora', $fechaHora)
                ->where('Usuario_idUsuarioCli', $idCliente)
                ->where('Usuario_idUsuarioBar', $idBarbero)
                ->first();

            if (!$citaExistente && !$facturaExistente) {
                $idsServicios = collect($datosCita['servicios'])
                    ->map(fn($nombreServicio) => $servicios[$nombreServicio]->idServicio)
                    ->all();
                $citasConServicios = DB::table('cita_servicio')
                    ->select('idCita')
                    ->whereIn('idServicio', $idsServicios)
                    ->groupBy('idCita')
                    ->havingRaw('COUNT(DISTINCT cita_servicio.idServicio) = ?', [count($idsServicios)])
                    ->pluck('idCita');

                $citaExistente = DB::table('cita')
                    ->where('Usuario_idUsuarioCli', $idCliente)
                    ->where('Usuario_idUsuarioBar', $idBarbero)
                    ->whereIn('idCita', $citasConServicios)
                    ->orderBy('idCita')
                    ->first();
            }

            $idCita = $facturaExistente?->Cita_idCita ?? $citaExistente?->idCita;

            if ($idCita) {
                DB::table('cita')
                    ->where('idCita', $idCita)
                    ->update([
                        'Fecha_hora' => $fechaHora,
                        'estado' => $datosCita['estado'],
                        'estado_pago' => $datosCita['estadoPago'],
                        'modalidad_pago' => $datosCita['modalidadPago'],
                        'Usuario_idUsuarioCli' => $idCliente,
                        'Usuario_idUsuarioBar' => $idBarbero,
                    ]);
            } else {
                DB::table('cita')->updateOrInsert(
                    [
                        'Fecha_hora' => $fechaHora,
                        'Usuario_idUsuarioCli' => $idCliente,
                        'Usuario_idUsuarioBar' => $idBarbero,
                    ],
                    [
                        'Fecha_hora' => $fechaHora,
                        'estado' => $datosCita['estado'],
                        'estado_pago' => $datosCita['estadoPago'],
                        'modalidad_pago' => $datosCita['modalidadPago'],
                        'Usuario_idUsuarioCli' => $idCliente,
                        'Usuario_idUsuarioBar' => $idBarbero,
                    ],
                );
                $idCita = DB::table('cita')
                    ->where('Fecha_hora', $fechaHora)
                    ->where('Usuario_idUsuarioCli', $idCliente)
                    ->where('Usuario_idUsuarioBar', $idBarbero)
                    ->value('idCita');
            }

            $horaSiguiente = $datosCita['fecha'];
            foreach ($datosCita['servicios'] as $nombreServicio) {
                $servicio = $servicios[$nombreServicio];

                DB::table('cita_servicio')->updateOrInsert(
                    [
                        'idCita' => $idCita,
                        'idServicio' => $servicio->idServicio,
                    ],
                    [
                        'idCita' => $idCita,
                        'idServicio' => $servicio->idServicio,
                        'fecha_hora_servicio' => $horaSiguiente->format('Y-m-d H:i:s'),
                    ],
                );

                $horaSiguiente = $horaSiguiente->addMinutes(max(1, (int) $servicio->Duracion));
            }

            $subtotal = collect($datosCita['servicios'])
                ->sum(fn($nombreServicio) => (int) $servicios[$nombreServicio]->Precio);

            if ($datosCita['estadoPago'] === 'Pagado') {
                $datosFactura = [
                    'numero_factura' => $numeroFactura,
                    'fecha_emision' => $fechaHora,
                    'subtotal' => $subtotal,
                    'total_pagar' => $subtotal,
                    'metodo_pago' => $datosCita['metodoPago'],
                    'estado_factura' => 'Pagada',
                    'Cita_idCita' => $idCita,
                    'Usuario_idUsuario' => $idCliente,
                ];

                if ($facturaExistente) {
                    DB::table('factura')
                        ->where('idfactura', $facturaExistente->idfactura)
                        ->update($datosFactura);
                } else {
                    DB::table('factura')->insert($datosFactura);
                }
            } else {
                DB::table('factura')->where('Cita_idCita', $idCita)->delete();
            }
        }
    }
}

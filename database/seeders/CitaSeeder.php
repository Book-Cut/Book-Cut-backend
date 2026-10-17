<?php

namespace Database\Seeders;

use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CitaSeeder extends Seeder
{
    public function run(): void
    {
        $usuarios = DB::table('usuario')->pluck('idUsuario', 'correo');
        $servicios = DB::table('servicio')->get()->keyBy('Nombre');
        $proximaFecha = static function (array $diasPermitidos, int $diasDesdeHoy): CarbonImmutable {
            $fecha = CarbonImmutable::now()->addDays($diasDesdeHoy)->startOfDay();

            while (! in_array($fecha->dayOfWeekIso, $diasPermitidos, true)) {
                $fecha = $fecha->addDay();
            }

            return $fecha;
        };

        $fechaCarlos = $proximaFecha([1, 2, 3, 4, 5], 2);
        $fechaAndres = $proximaFecha([2, 3, 4, 5, 6], 3);
        $citas = [
            [
                'codigo' => 'PROXIMA-LUIS',
                'fecha' => $fechaCarlos->setTime(9, 0),
                'estado' => 'Confirmado',
                'cliente' => 'luis@gmail.com',
                'barbero' => 'carlos@bookcut.com',
                'servicios' => ['Corte y Barba Premium'],
            ],
            [
                'codigo' => 'PENDIENTE-MIGUEL',
                'fecha' => $fechaCarlos->setTime(11, 0),
                'estado' => 'Pendiente',
                'cliente' => 'miguel@gmail.com',
                'barbero' => 'carlos@bookcut.com',
                'servicios' => ['Corte Clasico', 'Perfilado de Barba'],
            ],
            [
                'codigo' => 'PROXIMA-SOFIA',
                'fecha' => $fechaAndres->setTime(10, 0),
                'estado' => 'Confirmado',
                'cliente' => 'sofia@gmail.com',
                'barbero' => 'andres@bookcut.com',
                'servicios' => ['Perfilado de Barba'],
            ],
            [
                'codigo' => 'CANCELADA-FUTURA',
                'fecha' => $fechaCarlos->setTime(9, 0),
                'estado' => 'Cancelado',
                'cliente' => 'sofia@gmail.com',
                'barbero' => 'carlos@bookcut.com',
                'servicios' => ['Corte Clasico'],
            ],
            [
                'codigo' => 'FINALIZADA-LUIS',
                'fecha' => CarbonImmutable::now()->subDays(10)->setTime(14, 0),
                'estado' => 'Finalizado',
                'cliente' => 'luis@gmail.com',
                'barbero' => 'andres@bookcut.com',
                'servicios' => ['Corte Clasico'],
            ],
            [
                'codigo' => 'CANCELADA-MIGUEL',
                'fecha' => CarbonImmutable::now()->subDays(5)->setTime(16, 0),
                'estado' => 'Cancelado',
                'cliente' => 'miguel@gmail.com',
                'barbero' => 'carlos@bookcut.com',
                'servicios' => ['Perfilado de Barba'],
            ],
        ];

        foreach ($citas as $datosCita) {
            $fechaHora = $datosCita['fecha']->format('Y-m-d H:i:s');
            $idCliente = $usuarios[$datosCita['cliente']];
            $idBarbero = $usuarios[$datosCita['barbero']];
            $numeroFactura = 'SEED-BOOKCUT-'.$datosCita['codigo'];
            $idCita = DB::table('factura')
                ->where('numero_factura', $numeroFactura)
                ->value('Cita_idCita');

            if ($idCita) {
                DB::table('cita')
                    ->where('idCita', $idCita)
                    ->update([
                        'Fecha_hora' => $fechaHora,
                        'estado' => $datosCita['estado'],
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
                ->sum(fn ($nombreServicio) => (int) $servicios[$nombreServicio]->Precio);

            DB::table('factura')->updateOrInsert(
                ['numero_factura' => $numeroFactura],
                [
                    'fecha_emision' => $fechaHora,
                    'subtotal' => $subtotal,
                    'total_pagar' => $subtotal,
                    'metodo_pago' => $datosCita['estado'] === 'Finalizado' ? 'Nequi' : 'Pendiente_Pago',
                    'estado_factura' => match ($datosCita['estado']) {
                        'Cancelado' => 'Anulada',
                        'Finalizado' => 'Pagada',
                        default => 'Emitida',
                    },
                    'Cita_idCita' => $idCita,
                    'Usuario_idUsuario' => $idCliente,
                ],
            );
        }
    }
}

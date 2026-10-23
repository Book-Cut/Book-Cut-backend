<?php

namespace App\Console\Commands;

use App\Models\Citas;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ProcesarConfirmacionesCitas extends Command
{
    protected $signature = 'citas:procesar-confirmaciones';

    protected $description = 'Solicita confirmación a dos horas y cancela citas sin respuesta';

    private bool $huboErrores = false;

    public function handle(): int
    {
        $this->cancelarConfirmacionesVencidas();
        $this->reintentarAvisosFallidos();
        $this->avisarCitasProximas();

        return $this->huboErrores ? self::FAILURE : self::SUCCESS;
    }

    private function cancelarConfirmacionesVencidas(): void
    {
        Citas::query()
            ->where('estado', 'Pendiente')
            ->whereNotNull('confirmacion_solicitada_at')
            ->where(function ($consulta) {
                $consulta->where('confirmacion_solicitada_at', '<=', now()->subMinutes(30))
                    ->orWhere('Fecha_hora', '<=', now());
            })
            ->select('idCita')
            ->chunkById(100, function ($citas) {
                foreach ($citas as $cita) {
                    $this->cancelarPorFaltaDeConfirmacion($cita->idCita);
                }
            }, 'idCita');
    }

    private function cancelarPorFaltaDeConfirmacion(int $idCita): void
    {
        $aviso = DB::transaction(function () use ($idCita) {
            $cita = Citas::with('cliente')->lockForUpdate()->find($idCita);
            if (
                ! $cita
                || $cita->estado !== 'Pendiente'
                || ! $cita->confirmacion_solicitada_at
                || ($cita->Fecha_hora > now()->toDateTimeString()
                    && $cita->confirmacion_solicitada_at->gt(now()->subMinutes(30)))
            ) {
                return null;
            }

            $cita->estado = 'Cancelado';
            $cita->confirmacion_solicitada_at = null;
            $cita->save();
            $cita->facturas()->update(['estado_factura' => 'Anulada']);

            DB::table('notificacion')
                ->where('Cita_idCita', $cita->idCita)
                ->where('evento', 'confirmacion')
                ->where('fecha_hora_cita', $cita->Fecha_hora)
                ->update(['token_confirmacion' => null]);

            $notificacion = $this->obtenerOCrearNotificacion(
                $cita,
                'cancelacion_sin_confirmar',
                'Tu cita fue cancelada porque no recibimos tu confirmación dentro de los 30 minutos.',
            );

            return [
                'idNotificacion' => $notificacion,
                'correo' => $cita->cliente?->correo,
                'nombre' => $cita->cliente?->Nombre ?? 'cliente',
                'fecha' => Carbon::parse($cita->Fecha_hora)->format('d/m/Y H:i'),
            ];
        });

        if ($aviso) {
            $this->enviarAvisoCancelacion($aviso);
        }
    }

    private function avisarCitasProximas(): void
    {
        $ahora = now();
        $desde = $ahora->copy()->addHours(2)->subMinutes(2);
        $hasta = $ahora->copy()->addHours(2)->addMinutes(2);

        Citas::query()
            ->where('estado', 'Confirmado')
            ->whereBetween('Fecha_hora', [$desde, $hasta])
            ->whereNotNull('fecha_agendada')
            ->whereRaw('fecha_agendada <= DATE_SUB(Fecha_hora, INTERVAL 2 HOUR)')
            ->whereNotExists(function ($consulta) {
                $consulta->selectRaw('1')
                    ->from('notificacion')
                    ->whereColumn('notificacion.Cita_idCita', 'cita.idCita')
                    ->whereColumn('notificacion.fecha_hora_cita', 'cita.Fecha_hora')
                    ->where('notificacion.evento', 'confirmacion');
            })
            ->select('idCita')
            ->chunkById(100, function ($citas) {
                foreach ($citas as $cita) {
                    $this->solicitarConfirmacion($cita->idCita);
                }
            }, 'idCita');
    }

    private function solicitarConfirmacion(int $idCita): void
    {
        $aviso = DB::transaction(function () use ($idCita) {
            $cita = Citas::with('cliente')->lockForUpdate()->find($idCita);
            if (
                ! $cita
                || ! in_array($cita->estado, ['Confirmado', 'Pendiente'], true)
                || ($cita->estado === 'Pendiente' && ! $cita->confirmacion_solicitada_at)
                || Carbon::parse($cita->Fecha_hora)->lte(now())
                || ! $cita->fecha_agendada
                || Carbon::parse($cita->fecha_agendada)->gt(Carbon::parse($cita->Fecha_hora)->subHours(2))
            ) {
                return null;
            }

            $notificacion = DB::table('notificacion')
                ->where('Cita_idCita', $cita->idCita)
                ->where('evento', 'confirmacion')
                ->where('fecha_hora_cita', $cita->Fecha_hora)
                ->lockForUpdate()
                ->first();

            if ($notificacion && (
                $notificacion->estado_envio === 'Enviado'
                || $notificacion->estado_envio === 'No disponible'
                || $notificacion->intentos >= 3
                || ($notificacion->ultimo_intento_at
                    && Carbon::parse($notificacion->ultimo_intento_at)->gt(now()->subMinutes(5)))
            )) {
                return null;
            }

            $tokenConfirmacion = bin2hex(random_bytes(32));
            $mensaje = 'Confirma tu asistencia dentro de los próximos 30 minutos. Si no confirmas, tu cita será cancelada. El plazo para reagendar ya pasó; puedes confirmar o cancelar desde Mis citas.';
            $idNotificacion = $notificacion
                ? $notificacion->idNotificacion
                : $this->obtenerOCrearNotificacion($cita, 'confirmacion', $mensaje);

            DB::table('notificacion')->where('idNotificacion', $idNotificacion)->update([
                'token_confirmacion' => hash('sha256', $tokenConfirmacion),
                'estado_envio' => 'Pendiente',
                'ultimo_intento_at' => now(),
                'intentos' => ($notificacion->intentos ?? 0) + 1,
            ]);

            $cita->estado = 'Pendiente';
            $cita->confirmacion_solicitada_at = now();
            $cita->save();

            return [
                'idNotificacion' => $idNotificacion,
                'tokenConfirmacion' => $tokenConfirmacion,
                'correo' => $cita->cliente?->correo,
                'nombre' => $cita->cliente?->Nombre ?? 'cliente',
                'fecha' => Carbon::parse($cita->Fecha_hora)->format('d/m/Y H:i'),
                'solicitadaEn' => $cita->confirmacion_solicitada_at,
            ];
        });

        if (! $aviso) {
            return;
        }

        if (! $aviso['correo']) {
            $this->marcarAvisoFallido($aviso['idNotificacion'], 'No disponible', $aviso['solicitadaEn']);
            Log::error('No se envió la confirmación: el cliente no tiene correo registrado.', [
                'id_notificacion' => $aviso['idNotificacion'],
            ]);
            $this->huboErrores = true;

            return;
        }

        $enlace = rtrim(config('app.frontend_url'), '/').'/confirmar-cita?token='.$aviso['tokenConfirmacion'];

        try {
            Mail::raw(
                "Hola {$aviso['nombre']},\n\nTu cita de Book&Cut está programada para {$aviso['fecha']}. "
                ."Confirma tu asistencia dentro de los próximos 30 minutos con este enlace: {$enlace}\n\n"
                .'Si no confirmas, la cita se cancelará automáticamente. A esta hora ya no puedes reagendar, '
                .'pero puedes confirmar o cancelar desde Mis citas.',
                fn ($mensaje) => $mensaje->to($aviso['correo'])->subject('Confirma tu asistencia a Book&Cut'),
            );

            DB::table('notificacion')->where('idNotificacion', $aviso['idNotificacion'])->update([
                'estado_envio' => 'Enviado',
                'enviado_at' => now(),
                'fecha_envio' => now()->toDateString(),
            ]);
        } catch (Throwable $errorEnvio) {
            $this->marcarAvisoFallido($aviso['idNotificacion'], 'Error', $aviso['solicitadaEn']);
            Log::error('No se pudo enviar el correo de confirmación de cita.', [
                'id_notificacion' => $aviso['idNotificacion'],
                'detalle' => $errorEnvio->getMessage(),
            ]);
            $this->huboErrores = true;
        }
    }

    private function reintentarAvisosFallidos(): void
    {
        DB::table('notificacion')
            ->whereIn('estado_envio', ['Error', 'Pendiente'])
            ->where('intentos', '<', 3)
            ->where('ultimo_intento_at', '<=', now()->subMinutes(5))
            ->whereIn('evento', ['confirmacion', 'cancelacion_sin_confirmar'])
            ->select('idNotificacion', 'Cita_idCita', 'evento')
            ->orderBy('idNotificacion')
            ->chunkById(100, function ($avisos) {
                foreach ($avisos as $aviso) {
                    if ($aviso->evento === 'confirmacion') {
                        $this->solicitarConfirmacion($aviso->Cita_idCita);
                    } else {
                        $this->reintentarAvisoCancelacion($aviso->idNotificacion);
                    }
                }
            }, 'idNotificacion');
    }

    private function reintentarAvisoCancelacion(int $idNotificacion): void
    {
        $aviso = DB::table('notificacion')
            ->join('cita', 'cita.idCita', '=', 'notificacion.Cita_idCita')
            ->leftJoin('usuario', 'usuario.idUsuario', '=', 'cita.Usuario_idUsuarioCli')
            ->where('notificacion.idNotificacion', $idNotificacion)
            ->whereIn('notificacion.estado_envio', ['Error', 'Pendiente'])
            ->where('notificacion.intentos', '<', 3)
            ->select(
                'notificacion.idNotificacion',
                'notificacion.mensaje',
                'notificacion.intentos',
                'notificacion.fecha_hora_cita',
                'usuario.correo',
                'usuario.Nombre',
            )
            ->first();

        if (! $aviso) {
            return;
        }

        $this->enviarAvisoCancelacion([
            'idNotificacion' => $aviso->idNotificacion,
            'correo' => $aviso->correo,
            'nombre' => $aviso->Nombre,
            'fecha' => Carbon::parse($aviso->fecha_hora_cita)->format('d/m/Y H:i'),
            'mensaje' => $aviso->mensaje,
        ]);
    }

    private function enviarAvisoCancelacion(array $aviso): void
    {
        if (! $aviso['correo']) {
            DB::table('notificacion')->where('idNotificacion', $aviso['idNotificacion'])->update([
                'estado_envio' => 'No disponible',
                'ultimo_intento_at' => now(),
                'intentos' => DB::raw('intentos + 1'),
            ]);
            Log::error('No se envió la cancelación: el cliente no tiene correo registrado.', [
                'id_notificacion' => $aviso['idNotificacion'],
            ]);
            $this->huboErrores = true;

            return;
        }

        DB::table('notificacion')->where('idNotificacion', $aviso['idNotificacion'])->update([
            'estado_envio' => 'Pendiente',
            'ultimo_intento_at' => now(),
            'intentos' => DB::raw('intentos + 1'),
        ]);

        try {
            Mail::raw(
                "Hola {$aviso['nombre']},\n\n{$aviso['mensaje']} "
                .($aviso['fecha'] ? "La hora programada era {$aviso['fecha']}." : ''),
                fn ($mensaje) => $mensaje->to($aviso['correo'])->subject('Tu cita de Book&Cut fue cancelada'),
            );

            DB::table('notificacion')->where('idNotificacion', $aviso['idNotificacion'])->update([
                'estado_envio' => 'Enviado',
                'enviado_at' => now(),
                'fecha_envio' => now()->toDateString(),
            ]);
        } catch (Throwable $errorEnvio) {
            DB::table('notificacion')->where('idNotificacion', $aviso['idNotificacion'])->update([
                'estado_envio' => 'Error',
            ]);
            Log::error('No se pudo enviar el correo de cancelación de cita.', [
                'id_notificacion' => $aviso['idNotificacion'],
                'detalle' => $errorEnvio->getMessage(),
            ]);
            $this->huboErrores = true;
        }
    }

    private function marcarAvisoFallido(int $idNotificacion, string $estado, mixed $solicitadaEn): void
    {
        DB::transaction(function () use ($idNotificacion, $estado, $solicitadaEn) {
            $citaId = DB::table('notificacion')
                ->where('idNotificacion', $idNotificacion)
                ->value('Cita_idCita');
            $cita = Citas::lockForUpdate()->find($citaId);

            $aviso = DB::table('notificacion')
                ->where('idNotificacion', $idNotificacion)
                ->lockForUpdate()
                ->first();

            if (! $aviso) {
                return;
            }

            DB::table('notificacion')
                ->where('idNotificacion', $idNotificacion)
                ->update([
                    'estado_envio' => $estado,
                    'token_confirmacion' => null,
                ]);

            if (
                $cita
                && $cita->estado === 'Pendiente'
                && $cita->confirmacion_solicitada_at?->equalTo($solicitadaEn)
            ) {
                $cita->estado = 'Confirmado';
                $cita->confirmacion_solicitada_at = null;
                $cita->save();
            }
        });
    }

    private function obtenerOCrearNotificacion(Citas $cita, string $evento, string $mensaje): int
    {
        $notificacion = DB::table('notificacion')
            ->where('Cita_idCita', $cita->idCita)
            ->where('evento', $evento)
            ->where('fecha_hora_cita', $cita->Fecha_hora)
            ->first();

        if ($notificacion) {
            return $notificacion->idNotificacion;
        }

        return DB::table('notificacion')->insertGetId([
            'tipo' => 'Correo',
            'evento' => $evento,
            'mensaje' => $mensaje,
            'fecha_envio' => now()->toDateString(),
            'fecha_hora_cita' => $cita->Fecha_hora,
            'Cita_idCita' => $cita->idCita,
            'estado_envio' => 'Pendiente',
            'intentos' => 0,
        ], 'idNotificacion');
    }
}

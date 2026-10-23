<?php

use App\Models\Citas;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Carbon;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('citas:procesar-confirmaciones', function () {
    $citasPendientes = Citas::query()
        ->where('estado', 'Pendiente')
        ->whereNotNull('confirmacion_solicitada_at')
        ->where('confirmacion_solicitada_at', '<=', now()->subMinutes(30))
        ->pluck('idCita');

    foreach ($citasPendientes as $idCita) {
        $cita = DB::transaction(function () use ($idCita) {
            $cita = Citas::with(['cliente', 'factura'])->lockForUpdate()->find($idCita);

            if (
                ! $cita
                || $cita->estado !== 'Pendiente'
                || ! $cita->confirmacion_solicitada_at
                || $cita->confirmacion_solicitada_at->gt(now()->subMinutes(30))
            ) {
                return null;
            }

            $cita->estado = 'Cancelado';
            $cita->save();
            $cita->factura()->update(['estado_factura' => 'Anulada']);

            return $cita;
        });

        if (! $cita || ! $cita->cliente?->correo) {
            continue;
        }

        try {
            $fecha = Carbon::parse($cita->Fecha_hora)->format('d/m/Y H:i');
            Mail::raw(
                "Hola {$cita->cliente->Nombre},\n\nTu cita de Book&Cut del {$fecha} fue cancelada "
                .'porque no recibimos tu confirmación dentro de los 30 minutos.',
                fn ($mensaje) => $mensaje->to($cita->cliente->correo)->subject('Tu cita de Book&Cut fue cancelada'),
            );
        } catch (\Throwable $error) {
            Log::error('No se pudo enviar el correo de cancelación de cita.', [
                'cita_id' => $cita->idCita,
                'error' => $error->getMessage(),
            ]);
        }
    }

    $citasPorConfirmar = Citas::query()
        ->where('estado', 'Confirmado')
        ->where('Fecha_hora', '>', now())
        ->where('Fecha_hora', '<=', now()->addHour())
        ->get(['idCita']);

    foreach ($citasPorConfirmar as $citaEncontrada) {
        $cita = DB::transaction(function () use ($citaEncontrada) {
            $cita = Citas::with('cliente')->lockForUpdate()->find($citaEncontrada->idCita);

            if (
                ! $cita
                || $cita->estado !== 'Confirmado'
                || Carbon::parse($cita->Fecha_hora)->lte(now())
                || Carbon::parse($cita->Fecha_hora)->gt(now()->addHour())
            ) {
                return null;
            }

            $cita->estado = 'Pendiente';
            $cita->confirmacion_solicitada_at = now();
            $cita->save();

            return $cita;
        });

        if (! $cita) {
            continue;
        }

        try {
            $correo = $cita->cliente?->correo;
            if (! $correo) {
                throw new \RuntimeException('El cliente no tiene un correo registrado.');
            }

            $fecha = Carbon::parse($cita->Fecha_hora)->format('d/m/Y H:i');
            Mail::raw(
                "Hola {$cita->cliente->Nombre},\n\nTu cita de Book&Cut está programada para {$fecha}. "
                .'Confirma tu asistencia en Mis citas dentro de los próximos 30 minutos. '
                .'Si no confirmas, la cita será cancelada.',
                fn ($mensaje) => $mensaje->to($correo)->subject('Confirma tu asistencia a Book&Cut'),
            );
        } catch (\Throwable $error) {
            Log::error('No se pudo enviar el correo de confirmación de cita.', [
                'cita_id' => $cita->idCita,
                'error' => $error->getMessage(),
            ]);

            DB::transaction(function () use ($cita) {
                $citaActual = Citas::lockForUpdate()->find($cita->idCita);

                if (
                    $citaActual
                    && $citaActual->estado === 'Pendiente'
                    && $citaActual->confirmacion_solicitada_at?->equalTo($cita->confirmacion_solicitada_at)
                ) {
                    $citaActual->estado = 'Confirmado';
                    $citaActual->confirmacion_solicitada_at = null;
                    $citaActual->save();
                }
            });
        }
    }
})->purpose('Solicita confirmación de citas próximas y cancela las que no respondan');

Schedule::command('citas:procesar-confirmaciones')->everyMinute()->withoutOverlapping();

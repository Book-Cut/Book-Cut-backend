<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Citas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificacionController extends Controller
{
    public function index(Request $solicitud)
    {
        $usuario = $solicitud->user();
        if (! $usuario || $usuario->roles?->Nombre_rol !== 'Cliente') {
            return response()->json(['message' => 'No tienes permiso para ver estas notificaciones.'], 403);
        }

        $notificaciones = DB::table('notificacion')
            ->join('cita', 'cita.idCita', '=', 'notificacion.Cita_idCita')
            ->where('cita.Usuario_idUsuarioCli', $usuario->idUsuario)
            ->whereIn('notificacion.evento', ['confirmacion', 'cancelacion_sin_confirmar'])
            ->where('notificacion.estado_envio', 'Enviado')
            ->orderByDesc('notificacion.idNotificacion')
            ->limit(20)
            ->get([
                'notificacion.idNotificacion',
                'notificacion.evento',
                'notificacion.mensaje',
                'notificacion.fecha_envio',
                'notificacion.fecha_hora_cita',
                'notificacion.estado_envio',
                'notificacion.leido_at',
            ]);

        return response()->json(['data' => $notificaciones]);
    }

    public function confirmarPorCorreo(Request $solicitud)
    {
        $datos = $solicitud->validate([
            'token' => 'required|string|size:64|regex:/^[a-f0-9]+$/',
        ]);

        return DB::transaction(function () use ($datos) {
            $huellaToken = hash('sha256', $datos['token']);
            $aviso = DB::table('notificacion')
                ->where('token_confirmacion', $huellaToken)
                ->where('evento', 'confirmacion')
                ->where('estado_envio', 'Enviado')
                ->first(['idNotificacion', 'Cita_idCita']);

            if (! $aviso) {
                return response()->json(['message' => 'El enlace de confirmación ya no es válido.'], 422);
            }

            $cita = Citas::lockForUpdate()->find($aviso->Cita_idCita);
            $aviso = DB::table('notificacion')
                ->where('idNotificacion', $aviso->idNotificacion)
                ->where('token_confirmacion', $huellaToken)
                ->where('evento', 'confirmacion')
                ->where('estado_envio', 'Enviado')
                ->lockForUpdate()
                ->first();

            if (
                ! $aviso
                || ! $cita
                || $cita->estado !== 'Pendiente'
                || ! $cita->confirmacion_solicitada_at
                || $cita->confirmacion_solicitada_at->copy()->addMinutes(30)->lte(now())
                || $cita->Fecha_hora !== $aviso->fecha_hora_cita
                || $cita->Fecha_hora <= now()->toDateTimeString()
            ) {
                return response()->json(['message' => 'El tiempo para confirmar esta cita terminó.'], 422);
            }

            $cita->estado = 'Confirmado';
            $cita->confirmacion_solicitada_at = null;
            $cita->save();

            DB::table('notificacion')->where('idNotificacion', $aviso->idNotificacion)->update([
                'token_confirmacion' => null,
                'leido_at' => now(),
            ]);

            return response()->json(['message' => 'Asistencia confirmada correctamente.']);
        });
    }
}

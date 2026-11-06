<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Citas;
use App\Models\citaAfectada;
use App\Models\indisponibilidad;
use App\Models\notificacionWeb;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class IndisponibilidadController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();
        $esAdmin = (int) $user->Roles_IDRol === 1;
        $esBarbero = (int) $user->Roles_IDRol === 2;

        if (!$esAdmin && !$esBarbero) {
            return response()->json(['result' => 'error', 'message' => 'No tienes permiso para crear indisponibilidad'], 403);
        }

        $datos = $request->validate([
            'Usuario_idUsuarioBar' => 'nullable|integer|exists:usuario,idUsuario',
            'fecha' => 'required|date|after_or_equal:today',
            'hora_inicio' => 'nullable|date_format:H:i|required_with:hora_fin',
            'hora_fin' => 'nullable|date_format:H:i|required_with:hora_inicio|after:hora_inicio',
            'motivo' => 'nullable|string|max:255',
        ]);

        $idBarbero = $esBarbero ? $user->idUsuario : ($datos['Usuario_idUsuarioBar'] ?? null);
        if ($idBarbero) {
            return response()->json(['result' => 'error', 'message' => 'indica el barbero']);
        }

        $citas = Citas::with(['cliente', 'barbero'])
        ->where('Usuario_idUsuarioBar', $idBarbero)
        ->whereDate('Fecha_hora', $datos['fecha'])
        ->whereIn('estado', ['pendiente', 'confirmado'])
        ->when(
            !empty($datos['hora_inicio']),
            fn ($q) => $q->whereTime('Fecha_hora', '>=', $datos['hora_inicio'])
            ->whereTime('Fecha_hora', '<', $datos['hora_fin'])
        )
    ->get();

    $avisos = DB::transaction(function () use ($datos, $idBarbero, $user, $citas) {
        $indisponibilidad = indisponibilidad::create([
            'Usuario_idUsuarioBar' => $idBarbero,
            'fecha' => $datos['fecha'],
            'hora_inicio' => $datos['hora_inicio'] ?? null,
            'hora_fin' => $datos['hora_fin'] ?? null,
            'motivo' => $datos['motivo'] ?? null,
        ]);

        $avisos = [];
        foreach ($citas as $cita) {
            $afectada = citaAfectada::create([
                'Citas_idCita' => $cita->idCita,
                'Indisponibilidad_idIndisponibilidad' => $indisponibilidad->idIndisponibilidad,
            ]);

            $mensaje = "Tu cita del {$cita->Fecha_hora} con {$cita->barbero->Nombre} no podrá realizarse."
            . ($datos['motivo'] ?? false ? " Motivo: {$datos['motivo']}." : '')
            . ' Elige si quieres cancelarla, reprogramarla o aceptar otro barbero.';

        notificacionWeb::create([
            'Usuario_idUsuario' => $cita->Usuario_idUsuarioCli,
            'CitaAfectada_id' => $afectada->idCitaAfectada,
            'titulo' => 'Tu cita fue afectada',
            'mensaje' => $mensaje,
        ]);

        $avisos[] = [$cita->cliente, $mensaje];
        }

        return ['id' => $indisponibilidad->idIndisponibilidad, 'avisos' => $avisos];

        });

        $enlace = rtrim(env('FRONTEND_URL', 'http://localhost:5173'), '/') . '/mis-citas';
        foreach ($avisos['avisos'] as [$cliente, $mensaje]) {
            try {
                Mail::raw("Hola {$cliente->Nombre},\n\n $mensaje\n\nPuedes gestionar tu cita aquí: $enlace", function ($message) use ($cliente) {
                    $message->to($cliente->Correo)
                        ->subject('Tu cita en Book&Cut ha sido afectada');
                });
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return response()->json([
            'result' => 'ok',
            'message' => 'Indisponibildad registrada y notificaciones enviadas',
            'idIndisponibiliad' => $avisos['id'],
            'citas_afectadas' => count($avisos['avisos']),
        ], 201);

    }
}

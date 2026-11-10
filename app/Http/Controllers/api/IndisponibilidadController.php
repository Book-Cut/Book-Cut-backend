<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Citas;
use App\Models\citaAfectada;
use App\Models\indisponibilidad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


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
    if (!$idBarbero) {
        return response()->json(['result' => 'error', 'message' => 'Indica el barbero'], 422);
    }

    $citas = Citas::with(['cliente', 'barbero'])
        ->where('Usuario_idUsuarioBar', $idBarbero)
        ->whereDate('Fecha_hora', $datos['fecha'])
        ->whereIn('estado', ['Pendiente', 'Confirmado'])
        ->whereNotNull('Usuario_idUsuarioCli')
        ->whereDoesntHave('afectadaPendiente')
        ->when(
            !empty($datos['hora_inicio']),
            fn ($q) => $q->whereTime('Fecha_hora', '>=', $datos['hora_inicio'])
                ->whereTime('Fecha_hora', '<', $datos['hora_fin'])
        )
        ->get();

    DB::transaction(function () use ($datos, $idBarbero, $user, $citas) {
        $bloqueo = indisponibilidad::create([
            'Usuario_idUsuarioBar' => $idBarbero,
            'fecha' => $datos['fecha'],
            'hora_inicio' => $datos['hora_inicio'] ?? null,
            'hora_fin' => $datos['hora_fin'] ?? null,
            'motivo' => $datos['motivo'] ?? null,
            'Usuario_idUsuarioReg' => $user->idUsuario,
        ]);

        foreach ($citas as $cita) {
            citaAfectada::avisar($cita, $bloqueo->idIndisponibilidad, $datos['motivo'] ?? null);
        }
    });

    return response()->json([
        'result' => 'ok',
        'message' => 'Indisponibilidad registrada y notificaciones enviadas',
        'citas_afectadas' => $citas->count(),
    ], 201);
}
}
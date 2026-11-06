<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\notificacionWeb;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificacionWebController extends Controller
{
    public function index(Request $request)
    {
        $id = $request->user()->idUsuario;
        $data = DB::table('notificacion_web as n')
            ->leftJoin('cita_afectada as c', 'c.idCitaAfectada', '=', 'n.CitaAfectada_id')
            ->where('n.Usuario_idUsuario', $id)
            ->orderByDesc('n.idNotificacionWeb')
            ->limit(50)
            ->get(['n.*', 'c.respuesta']);

        return response()->json(['data' => $data, 'no_leidas' => $data->where('leida', 0)->count()]);
    }

    public function marcarLeida(Request $request, int $id)
    {
        notificacionWeb::where('idNotificacionWeb', $id)
            ->where('Usuario_idUsuario', $request->user()->idUsuario)
            ->update(['leida' => true]);

        return response()->json(['result' => 'ok']);
    }
}

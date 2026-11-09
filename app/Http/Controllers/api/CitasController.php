<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Citas;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class CitasController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if (!$user || !$user->roles) {
            return response()->json(['result' => 'error', 'message' => 'Rol no definido'], 403);
        }

        // Ejecutar procesamiento de confirmaciones de forma pasiva (máximo una vez cada 60 segundos)
        // para servidores donde no corre el cron en segundo plano.
        try {
            Cache::remember('citas_procesar_confirmaciones_lock', 60, function () {
                Artisan::call('citas:procesar-confirmaciones');
                return now()->timestamp;
            });
        } catch (\Throwable $e) {
            // Silencioso para no romper la petición del usuario si falla algún correo
        }

        $query = Citas::with([
            'cliente:idUsuario,Nombre,correo,telefono',
            'barbero:idUsuario,Nombre,correo,telefono,especialidad,foto_perfil',
            'servicios',
            'factura.beneficio',
            'facturas.beneficio',
            'valora',
            'afectadaPendiente.indisponibilidad',
        ]);

        if ($user->roles->Nombre_rol === 'Administrador') {
            return response()->json($query->get(), 200);
        }

        if ($user->roles->Nombre_rol === 'Barbero') {
            return response()->json($query->where('Usuario_idUsuarioBar', $user->idUsuario)->get(), 200);
        }

        if ($user->roles->Nombre_rol === 'Cliente') {
            return response()->json($query->where('Usuario_idUsuarioCli', $user->idUsuario)->get(), 200);
        }

        return response()->json(['result' => 'error', 'message' => 'sin permisos'], 403);
    }
}

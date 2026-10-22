<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Citas;
use Illuminate\Support\Facades\Auth;

class CitasController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if (!$user || !$user->roles) {
            return response()->json(['result' => 'error', 'message' => 'Rol no definido'], 403);
        }

        $query = Citas::with([
            'cliente:idUsuario,Nombre,correo,telefono',
            'barbero:idUsuario,Nombre,correo,telefono,especialidad',
            'servicios',
            'factura.beneficio',
            'valora',
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

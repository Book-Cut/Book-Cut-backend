<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Citas;
use Illuminate\Support\Facades\Auth;

class CitasController extends Controller
{
    public function index()
    {
        $user = Auth::user();
      
        if (!$user || !$user->roles) {
            return response()->json(['message' => 'Rol no definido'], 403);
        }

        if ($user->roles->Nombre_rol === 'Administrador') {
            $citas = Citas::with('usuario')->get();
            return response()->json($citas);
        }

        if ($user->roles->Nombre_rol === 'Cliente') {
            $citas = Citas::with('usuario')->where('Usuario_idUsuarioCli', $user->idUsuario)->get();
            return response()->json($citas);
        }

        return response()->json(['message' => 'sin permisos'], 403);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        
        if (!$user || !$user->roles) {
            return response()->json(['message' => 'Rol no definido'], 403);
        }

        if ($user->roles->Nombre_rol === 'Administrador') {
            $citas = Citas::create($request->all());
            return response()->json($citas);
        }

        if ($user->roles->Nombre_rol === 'Cliente') {
            $request->merge(['Usuario_idUsuarioCli' => $user->idUsuario]);
            $citas = Citas::create($request->all());
            return response()->json($citas);
        }
        
        return response()->json(['message' => 'sin permisos'], 403);
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        
        if (!$user || !$user->roles) {
            return response()->json(['message' => 'Rol no definido'], 403);
        }
      
        $cita = Citas::find($id);
        if (!$cita) {
            return response()->json(['message' => 'Cita no encontrada'], 404);
        }

        if ($user->roles->Nombre_rol == 'Administrador') {
            $cita->update($request->all());
            return response()->json(['message' => 'Cita actualizada correctamente', 'cita' => $cita]);
        }

        if ($user->roles->Nombre_rol === 'Cliente') {
            if ($cita->Usuario_idUsuarioCli !== $user->idUsuario) {
                return response()->json(['message' => 'No tienes permiso para actualizar esta cita'], 403);
            }

            $cita->update($request->only([
                'Fecha_hora',
                'estado',
                'barbero_idbarbero'
            ]));

            return response()->json($cita);
        }

        return response()->json(['message' => 'sin permisos'], 403);
    }

    public function destroy(string $id)
    {
        $user = Auth::user();
        
        if (!$user || !$user->roles) {
            return response()->json(['message' => 'Rol no definido'], 403);
        }

        $cita = Citas::find($id);
        if (!$cita) {
            return response()->json(['message' => 'Cita no encontrada'], 404);
        }

        if ($user->roles->Nombre_rol === 'Cliente' && $cita->Usuario_idUsuarioCli !== $user->idUsuario) {
            return response()->json(['message' => 'No tienes permiso para eliminar esta cita'], 403);
        }

        $cita->delete();
        return response()->json(['message' => 'Cita eliminada']);
    }
}
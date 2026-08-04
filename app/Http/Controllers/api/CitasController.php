<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Citas;
use Illuminate\Support\Facades\Auth;



class CitasController extends Controller
{
    //
    public function index()
    {

        $user = Auth::user();
        #si el rol no existe o el usuer tampoco existe
        if (! $user || ! $user->roles) {
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
        //
        $cita = Citas::create($request->all());
        return response()->json($cita, 201);
    }





    public function update(Request $request, string $id)
    {
        //
        $cita = Citas::find($id);
        if ($cita) {
            $cita->update($request->all());
            return response()->json(['message' => 'Cita actualizada correctamente', 'cita' => $cita]);
        } else {
            return response()->json(['message' => 'Cita no encontrada'], 404);
        }
    }

    public function destroy(string $id)
    {
        //
        $cita = Citas::find($id);
        if ($cita) {
            $cita->delete();
            return response()->json(['message' => 'Cita eliminada']);
        } else {
            return response()->json(['message' => 'Cita no encontrada'], 404);
        }
    }
}

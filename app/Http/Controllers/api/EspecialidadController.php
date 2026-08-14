<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Usuario;
use App\Models\Servicio;
use Illuminate\Support\Facades\Gate;

class EspecialidadController extends Controller
{
    public function asignarEspecialidades(Request $request, $idUsuarioBar)
    {

        if ($request->user()->roles_idrol !== 1) {
            return response()->json(['error' => 'Forbidden. Privilegios insuficientes.'], 403);
        }


        $validated = $request->validate([
            'servicios' => 'required|array',
            'servicios.*' => 'exists:servicios,idServicio'
        ]);


        $barbero = Usuario::where('idUsuario', $idUsuarioBar)
            ->where('roles_idrol', 2)
            ->firstOrFail();


        $barbero->especialidades()->sync($validated['servicios']);

        return response()->json([
            'message' => 'Especialidades sincronizadas correctamente.',
            'especialidades_actuales' => $barbero->especialidades()->pluck('idServicio')
        ], 200);
    }
}
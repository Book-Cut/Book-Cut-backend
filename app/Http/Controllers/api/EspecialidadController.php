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
            return response()->json(['result' => 'error', 'message' => 'Forbidden. Privilegios insuficientes.'], 403);
        }


        $validated = $request->validate(
            [
                'servicios' => 'required|array',
                'servicios.*' => 'exists:servicios,idServicio'
            ],
            [
                'servicios.required' => 'El campo servicios es obligatorio.',
                'servicios.array' => 'El campo servicios debe ser un arreglo.',
                'servicios.*.exists' => 'Uno o más servicios seleccionados no existen.'
            ]
        );


        $barbero = Usuario::where('idUsuario', $idUsuarioBar)
            ->where('roles_idrol', 2)
            ->firstOrFail();


        $barbero->especialidades()->sync($validated['servicios']);

        return response()->json([
            'result' => 'ok',
            'message' => 'Especialidades sincronizadas correctamente.',
            'especialidades_actuales' => $barbero->especialidades()->pluck('idServicio')
        ], 200);
    }
}
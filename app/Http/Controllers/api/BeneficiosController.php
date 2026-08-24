<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\beneficios;
use Illuminate\Support\Facades\Auth;



class BeneficiosController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $beneficios = beneficios::all();
        return response()->json(['result' => 'ok', 'message' => 'Beneficios obtenidos exitosamente', 'data' => $beneficios]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        if (!$user || !$user->roles) {
            return response()->json(['result' => 'error', 'message' => 'Rol no definido'], 403);
        }

        if ($user->roles->Nombre_rol !== 'Administrador') {
            return response()->json([
                'result' => 'error',
                'message' => 'No tienes permisos para crear beneficios.'
            ], 403);
        }

        $request

            ->validate([
                'titulo' => 'required',
                'Tipo_beneficio' => 'required',
                'Fecha_inicio' => 'required|date',
                'Fecha_fin' => 'required|date|after_or_equal:Fecha_inicio',
                'Usuario_idUsuario' => 'required',
            ], [
                'titulo.required' => 'El campo titulo es obligatorio.',
                'Tipo_beneficio.required' => 'El campo Tipo_beneficio es obligatorio.',
                'Fecha_inicio.required' => 'El campo Fecha_inicio es obligatorio.',
                'Fecha_inicio.date' => 'El campo Fecha_inicio debe ser una fecha válida.',
                'Fecha_fin.required' => 'El campo Fecha_fin es obligatorio.',
                'Fecha_fin.date' => 'El campo Fecha_fin debe ser una fecha válida.',
                'Fecha_fin.after_or_equal' => 'El campo Fecha_fin debe ser una fecha posterior o igual a Fecha_inicio.',
                'Usuario_idUsuario.required' => 'El campo Usuario_idUsuario es obligatorio.',
            ]);

        $beneficios = beneficios::create($request->all());
        return response()->json(['result' => 'ok', 'message' => 'Beneficio creado correctamente', 'beneficios' => $beneficios], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
        $beneficios = beneficios::find($id);
        if ($beneficios) {
            return response()->json(['result' => 'ok', 'message' => 'Beneficio obtenido exitosamente', 'data' => $beneficios]);
        } else {
            return response()->json(['result' => 'error', 'message' => 'Beneficio no encontrado'], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = Auth::user();

        if (!$user || !$user->roles) {
            return response()->json(['result' => 'error', 'message' => 'Rol no definido'], 403);
        }

        if ($user->roles->Nombre_rol !== 'Administrador') {
            return response()->json([
                'result' => 'error',
                'message' => 'No tienes permisos para actualizar beneficios.'
            ], 403);
        }

        $request->validate([
            'titulo' => 'required',
            'Tipo_beneficio' => 'required',
            'Fecha_inicio' => 'required|date',
            'Fecha_fin' => 'required|date|after_or_equal:Fecha_inicio',
            'Usuario_idUsuario' => 'required',
        ]);

        $beneficios = beneficios::find($id);
        if ($beneficios) {
            $beneficios->update($request->all());
            return response()->json(['result' => 'ok', 'message' => 'Beneficio actualizado correctamente', 'beneficios' => $beneficios]);
        } else {
            return response()->json(['result' => 'error', 'message' => 'Beneficio no encontrado'], 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = Auth::user();

        if (!$user || !$user->roles) {
            return response()->json(['result' => 'error', 'message' => 'Rol no definido'], 403);
        }

        if ($user->roles->Nombre_rol !== 'Administrador') {
            return response()->json([
                'result' => 'error',
                'message' => 'No tienes permisos para eliminar beneficios.'
            ], 403);
        }

        $beneficios = beneficios::find($id);
        if ($beneficios) {
            $beneficios->delete();
            return response()->json(['result' => 'ok', 'message' => 'Beneficio eliminado correctamente']);
        } else {
            return response()->json(['result' => 'error', 'message' => 'Beneficio no encontrado'], 404);
        }
    }
}


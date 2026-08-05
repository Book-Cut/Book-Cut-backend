<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\horario;
use Illuminate\Support\Facades\Auth;

class horarioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $horarios = horario::all();
        return response()->json($horarios);

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        if (!$user || !$user->roles) {
            return response()->json(['message' => 'Rol no definido'], 403);
        }

        if ($user->roles->Nombre_rol !== 'Administrador') {
            return response()->json([
                'message' => 'No tienes permisos para crear horarios.'
            ], 403);
        }

        $horarios = horario::create($request->all());
        return response()->json(['message' => 'Horario creado correctamente', 'horarios' => $horarios], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
        $horario = horario::find($id);
        return response()->json($horario);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = Auth::user();

        if (!$user || !$user->roles) {
            return response()->json(['message' => 'Rol no definido'], 403);
        }

        if ($user->roles->Nombre_rol !== 'Administrador') {
            return response()->json([
                'message' => 'No tienes permisos para actualizar horarios.'
            ], 403);
        }

        $horario = horario::find($id);
        if ($horario) {
            $horario->update($request->all());
            return response()->json(['message' => 'Horario actualizado correctamente', 'horario' => $horario]);
        } else {
            return response()->json(['message' => 'Horario no encontrado'], 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = Auth::user();

        if (!$user || !$user->roles) {
            return response()->json(['message' => 'Rol no definido'], 403);
        }

        if ($user->roles->Nombre_rol !== 'Administrador') {
            return response()->json([
                'message' => 'No tienes permisos para eliminar horarios.'
            ], 403);
        }

        $horario = horario::find($id);
        if ($horario) {
            $horario->delete();
            return response()->json(['message' => 'Horario eliminado correctamente']);
        } else {
            return response()->json(['message' => 'Horario no encontrado'], 404);
        }
    }
}

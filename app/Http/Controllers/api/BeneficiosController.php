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
        return response()->json($beneficios);
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
                'message' => 'No tienes permisos para crear beneficios.'
            ], 403);
        }

        $beneficios = beneficios::create($request->all());
        return response()->json(['message' => 'Beneficio creado correctamente', 'beneficios' => $beneficios], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
        $beneficios = beneficios::find($id);
        if ($beneficios) {
            return response()->json($beneficios);
        } else {
            return response()->json(['message' => 'Beneficio no encontrado'], 404);
        }
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
                'message' => 'No tienes permisos para actualizar beneficios.'
            ], 403);
        }

        $beneficios = beneficios::find($id);
        if ($beneficios) {
            $beneficios->update($request->all());
            return response()->json(['message' => 'Beneficio actualizado correctamente', 'beneficios' => $beneficios]);
        } else {
            return response()->json(['message' => 'Beneficio no encontrado'], 404);
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
                'message' => 'No tienes permisos para eliminar beneficios.'
            ], 403);
        }

        $beneficios = beneficios::find($id);
        if ($beneficios) {
            $beneficios->delete();
            return response()->json(['message' => 'Beneficio eliminado correctamente']);
        } else {
            return response()->json(['message' => 'Beneficio no encontrado'], 404);
        }
    }
}


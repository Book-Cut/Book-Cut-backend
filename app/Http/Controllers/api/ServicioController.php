<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Citas;
use App\Models\Servicio;
use Illuminate\Support\Facades\Auth;

class ServicioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {


        $servicios = Servicio::all();
        return response()->json(['result' => 'ok', 'message' => 'Servicios obtenidos correctamente', 'data' => $servicios]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        if ($user->roles->Nombre_rol !== 'Administrador') {
            return response()->json([
                'result' => 'error',
                'message' => 'No tienes permisos para crear servicios.'
            ], 403);
        }

        $servicio = Servicio::create($request->all());
        return response()->json(['result' => 'ok', 'message' => 'Servicio creado correctamente', 'data' => $servicio], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = Auth::user();

        if ($user->roles->Nombre_rol !== 'Administrador') {
            return response()->json([
                'result' => 'error',
                'message' => 'No tienes permisos para ver servicios.'
            ], 403);
        }

        $servicio = Servicio::find($id);
        if (!$servicio) {
            return response()->json(['result' => 'error', 'message' => 'Servicio not found'], 404);
        }
        return response()->json(['result' => 'ok', 'data' => $servicio]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = Auth::user();

        if ($user->roles->Nombre_rol !== 'Administrador') {
            return response()->json([
                'result' => 'error',
                'message' => 'No tienes permisos para actualizar servicios.'
            ], 403);
        }

        $servicio = Servicio::find($id);
        if (!$servicio) {
            return response()->json(['result' => 'error', 'message' => 'Servicio not found'], 404);
        }
        $servicio->update($request->all());
        return response()->json(['result' => 'ok', 'message' => 'Servicio actualizado correctamente', 'data' => $servicio]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $servicio = Servicio::find($id);
        $user = Auth::user();

        if ($user->roles->Nombre_rol !== 'Administrador') {
            return response()->json([
                'result' => 'error',
                'message' => 'No tienes permisos para eliminar servicios.'
            ], 403);
        }

        if (!$servicio) {
            return response()->json(['result' => 'error', 'message' => 'Servicio not found'], 404);
        }

        $existeEnCita = Citas::where('Servicio_idServicio', $servicio->idServicio)->exists();

        if ($existeEnCita) {
            return response()->json(
                ['result' => 'error', 'message' => 'No se puede eliminar el servicio porque existe en una cita'],
                409
            );
        }

        $servicio->delete();

        return response()->json(['result' => 'ok', 'message' => 'Servicio deleted']);
    }
}
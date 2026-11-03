<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Citas;
use App\Models\Servicio;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ServicioController extends Controller
{
    /**
     * Display a listing of the resource. no
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

        if (!$user || !$user->roles || $user->roles->Nombre_rol !== 'Administrador') {
            return response()->json([
                'result' => 'error',
                'message' => 'No tienes permisos para crear servicios.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'Nombre' => 'required|string|max:255',
            'Duracion' => 'required|integer|min:1',
            'Precio' => 'required|integer|min:0|max:2147483647',
        ], [
            'Nombre.required' => 'El nombre del servicio es obligatorio.',
            'Nombre.string' => 'El nombre del servicio debe ser texto.',
            'Nombre.max' => 'El nombre del servicio no puede superar los 255 caracteres.',
            'Duracion.required' => 'La duración del servicio es obligatoria.',
            'Duracion.integer' => 'La duración debe ser un número entero de minutos.',
            'Duracion.min' => 'La duración debe ser de al menos 1 minuto.',
            'Precio.required' => 'El precio del servicio es obligatorio.',
            'Precio.integer' => 'El precio debe ser un número entero.',
            'Precio.min' => 'El precio no puede ser negativo.',
            'Precio.max' => 'El precio supera el máximo permitido de $2,147,483,647. Ingresa un importe menor.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'result' => 'error',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        $servicio = Servicio::create($validator->validated());

        return response()->json([
            'result' => 'ok',
            'message' => 'Servicio creado correctamente',
            'data' => $servicio
        ], 201);
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

        $existeEnCita = Citas::whereHas('servicios', function ($query) use ($servicio) {
            $query->where('servicio.idServicio', $servicio->idServicio);
        })->exists();

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

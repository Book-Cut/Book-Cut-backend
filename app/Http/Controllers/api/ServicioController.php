<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Citas;
use App\Models\Servicio;

class ServicioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $servicios = Servicio::all();
        return response()->json($servicios);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        $servicio = Servicio::create($request->all());
        return response()->json($servicio, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
        $servicio = Servicio::find($id);
        if (!$servicio) {
            return response()->json(['message' => 'Servicio not found'], 404);
        }
        return response()->json($servicio);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
        $servicio = Servicio::find($id);
        if (!$servicio) {
            return response()->json(['message' => 'Servicio not found'], 404);
        }
        $servicio->update($request->all());
        return response()->json($servicio);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $servicio = Servicio::find($id);

        if (!$servicio) {
            return response()->json(['message' => 'Servicio not found'], 404);
        }

        $existeEnCita = Citas::where('Servicio_idServicio', $servicio->idServicio)->exists();

        if ($existeEnCita) {
            return response()->json(
                ['message' => 'No se puede eliminar el servicio porque existe en una cita'],
                409
            );
        }

        $servicio->delete();

        return response()->json(['message' => 'Servicio deleted']);
    }
}

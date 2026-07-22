<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Citas;


class CitasController extends Controller
{
    //
    public function index()
    {
        $citas = Citas::all();
        return response()->json($citas);
    }

    public function store(Request $request)
    {
        //
        $cita = Citas::create($request->all());
        return response()->json(['message' => 'Cita creada correctamente', 'cita' => $cita], 201);

    }




    public function show(string $id)
    {
        //
        $cita = Citas::find($id);
        if ($cita) {
            return response()->json($cita);
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

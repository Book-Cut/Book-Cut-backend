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
        $citas = Citas::create($request->all());
        return response()->json(['message' => 'Cita creada correctamente', 'citas' => $citas], 201);


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

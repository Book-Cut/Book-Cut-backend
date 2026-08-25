<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Valora;

class ValoraController extends Controller
{
    //
    public function index()
    {
        $valoras = Valora::with('cita', 'cita.getBarbero:idUsuario,Nombre', 'cita.getCliente:idUsuario,Nombre')->get();
        return response()->json(['result' => 'ok', 'message' => 'Valoraciones obtenidas correctamente', 'data' => $valoras]);
    }

    public function show(string $id)
    {
        //
        $valora = Valora::find($id);
        if (!$valora) {
            return response()->json(['result' => 'error', 'message' => 'Valoración no encontrada'], 404);
        }
        return response()->json(['result' => 'ok', 'data' => $valora]);
    }

    public function store(Request $request)
    {
        //
        $valora = Valora::create($request->all());
        return response()->json(['result' => 'ok', 'message' => 'Valoración creada correctamente', 'valora' => $valora], 201);
    }

    public function update(Request $request, string $id)
    {
        //
        $valora = Valora::find($id);
        if ($valora) {
            $valora->update($request->all());
            return response()->json(['result' => 'ok', 'message' => 'Valoración actualizada correctamente', 'valora' => $valora]);
        } else {
            return response()->json(['result' => 'error', 'message' => 'Valoración no encontrada'], 404);
        }
    }

    public function destroy(string $id)
    {
        //
        $valora = Valora::find($id);
        if ($valora) {
            $valora->delete();
            return response()->json(['result' => 'ok', 'message' => 'Valoración eliminada']);
        } else {
            return response()->json(['result' => 'error', 'message' => 'Valoración no encontrada'], 404);
        }
    }
}

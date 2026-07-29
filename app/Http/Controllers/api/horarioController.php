<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\horario;


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
        //
        $horario = horario::create($request->all());
        return response()->json($horario, 201);
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
        //
        $horario = horario::find($id);
        //return response()->json(['request' => $request->all(), 'horario' => $horario]);
        $horario->update($request->all());
        return response()->json($horario);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
        $horario = horario::find($id);
        $horario->delete();
        return response()->json(['message' => 'Horario eliminado correctamente'], 204);
    }
}

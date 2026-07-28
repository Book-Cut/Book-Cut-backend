<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\beneficios;



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
        //
        $beneficios = beneficios::create($request->all());
        return response()->json(['message' => 'Beneficio creado correctamente', 'beneficios' => $beneficios], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
        $beneficio = beneficios::find($id);
        if ($beneficio) {
            return response()->json($beneficio);
        } else {
            return response()->json(['message' => 'Beneficio no encontrado'], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
        $beneficio = beneficios::find($id);
        if ($beneficio) {
            //return response()->json(['beneficio' => $beneficio, 'request' => $request->all()]);
            $beneficio->update($request->all());

            return response()->json(['message' => 'Beneficio actualizado correctamente', 'beneficio' => $beneficio]);
        } else {
            return response()->json(['message' => 'Beneficio no encontrado'], 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
        $beneficio = beneficios::find($id);
        if ($beneficio) {
            $beneficio->delete();
            return response()->json(['message' => 'Beneficio eliminado correctamente']);
        } else {
            return response()->json(['message' => 'Beneficio no encontrado'], 404);
        }
    }
}


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
        $validator = \Validator::make($request->all(), [
            'EvaluacionBar' => 'required|integer|min:1|max:5',
            'EvaluacionCli' => 'required|integer|min:1|max:5',
            'Fecha_evaluacion_bar' => 'required|date',
            'Fecha_evaluacion_cita' => 'required|date',

        ], [
            'EvaluacionBar.required' => 'La evaluación del barbero es obligatoria',
            'EvaluacionBar.integer' => 'La evaluación del barbero debe ser un número entero',
            'EvaluacionBar.min' => 'La evaluación del barbero debe ser al menos 1',
            'EvaluacionBar.max' => 'La evaluación del barbero no puede ser mayor a 5',
            'EvaluacionCli.required' => 'La evaluación de la cita es obligatoria',
            'EvaluacionCli.integer' => 'La evaluación de la cita debe ser un número entero',
            'EvaluacionCli.min' => 'La evaluación de la cita debe ser al menos 1',
            'EvaluacionCli.max' => 'La evaluación de la cita no puede ser mayor a 5',
            'Fecha_evaluacion_bar.required' => 'La fecha de evaluación del barbero es obligatoria',
            'Fecha_evaluacion_bar.date' => 'La fecha de evaluación del barbero debe ser una fecha válida',
            'Fecha_evaluacion_cita.required' => 'La fecha de evaluación de la cita es obligatoria',
            'Fecha_evaluacion_cita.date' => 'La fecha de evaluación de la cita debe ser una fecha válida',
        ]);
        if ($validator->fails()) {
            return response()->json(['result' => 'error', 'message' => 'Datos inválidos', 'errors' => $validator->errors()], 422);
        }
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

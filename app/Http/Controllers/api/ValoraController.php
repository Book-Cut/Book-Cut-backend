<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Citas;
use App\Models\Valora;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ValoraController extends Controller
{
    public function guardarValoracionCita(Request $request, string $id)
    {
        $cita = Citas::find($id);

        if (!$cita) {
            return response()->json(['result' => 'error', 'message' => 'Cita no encontrada'], 404);
        }

        $request->validate([
            'puntuacion' => 'required|integer|min:1|max:5',
            'comentario' => 'nullable|string|max:500',
        ], [
            'puntuacion.required' => 'La puntuación es obligatoria',
            'puntuacion.integer' => 'La puntuación debe ser un número entero',
            'puntuacion.min' => 'La puntuación debe ser al menos 1',
            'puntuacion.max' => 'La puntuación no puede ser mayor a 5',
            'comentario.string' => 'El comentario debe ser texto',
            'comentario.max' => 'El comentario no puede superar 500 caracteres',
        ]);

        $valoracion = Valora::create([
            'EvaluacionBar' => $request->puntuacion,
            'EvaluacionCita' => $request->puntuacion,
            'Fecha_evaluacion_bar' => now(),
            'Fecha_evaluacion_cita' => now(),
            'Comentario' => $request->comentario,
        ]);

        $cita->Valora_Idvalora = $valoracion->Idvalora;
        $cita->save();

        return response()->json([
            'message' => 'Valoración creada correctamente',
            'data' => [
                'idValora' => $valoracion->Idvalora,
                'puntuacion' => $valoracion->EvaluacionBar,
                'comentario' => $valoracion->Comentario,
                'fecha_valoracion' => $valoracion->Fecha_evaluacion_cita,
            ],
        ], 200);
    }

    public function listarValoraciones()
    {
        $usuario = auth()->user();

        if (!$usuario) {
            return response()->json(['message' => 'No autenticado'], 401);
        }

        $consulta = DB::table('valora as v')
            ->leftJoin('cita as c', 'c.Valora_Idvalora', '=', 'v.Idvalora')
            ->leftJoin('usuario as cli', 'cli.idUsuario', '=', 'c.Usuario_idUsuarioCli')
            ->leftJoin('usuario as bar', 'bar.idUsuario', '=', 'c.Usuario_idUsuarioBar')
            ->leftJoin('cita_servicio as cs', 'cs.idCita', '=', 'c.idCita')
            ->leftJoin('servicio as s', 's.idServicio', '=', 'cs.idServicio')
            ->select([
                'v.Idvalora as idValora',
                'c.idCita as idCita',
                'v.EvaluacionBar as puntuacion',
                'v.Comentario as comentario',
                'v.Fecha_evaluacion_cita as fecha_valoracion',
                'c.Fecha_hora as Fecha_hora',
                'cli.Nombre as Cliente',
                'bar.Nombre as Barbero',
                DB::raw("GROUP_CONCAT(DISTINCT s.Nombre SEPARATOR ', ') as Servicios")
            ])
            ->groupBy(
                'v.Idvalora',
                'c.idCita',
                'v.EvaluacionBar',
                'v.Comentario',
                'v.Fecha_evaluacion_cita',
                'c.Fecha_hora',
                'cli.Nombre',
                'bar.Nombre'
            );

        if ($usuario->Roles_IDRol == 2) {
            $consulta->where(function ($query) use ($usuario) {
                $query->where('c.Usuario_idUsuarioBar', $usuario->idUsuario)
                    ->orWhereNull('c.idCita');
            });
        }

        $lista = $consulta->get()->map(function ($item) use ($usuario) {
            $fechaValoracion = $item->fecha_valoracion ?? $item->Fecha_hora;
            $cliente = $item->Cliente ?? '';
            $servicios = $item->Servicios ?? '';

            if ((!$item->idCita || empty($cliente)) && $usuario->Roles_IDRol == 2) {
                $citaCercana = DB::table('cita as c')
                    ->leftJoin('usuario as cli', 'cli.idUsuario', '=', 'c.Usuario_idUsuarioCli')
                    ->leftJoin('cita_servicio as cs', 'cs.idCita', '=', 'c.idCita')
                    ->leftJoin('servicio as s', 's.idServicio', '=', 'cs.idServicio')
                    ->where('c.Usuario_idUsuarioBar', $usuario->idUsuario)
                    ->where(function ($query) use ($fechaValoracion) {
                        if ($fechaValoracion) {
                            $query->whereDate('c.Fecha_hora', date('Y-m-d', strtotime($fechaValoracion)))
                                ->orWhere('c.Fecha_hora', '>=', $fechaValoracion);
                        }
                    })
                    ->select([
                        'c.idCita',
                        'cli.Nombre as Cliente',
                        DB::raw("GROUP_CONCAT(DISTINCT s.Nombre SEPARATOR ', ') as Servicios")
                    ])
                    ->groupBy('c.idCita', 'cli.Nombre')
                    ->orderBy('c.Fecha_hora', 'desc')
                    ->first();

                if ($citaCercana) {
                    $cliente = $citaCercana->Cliente ?? $cliente;
                    $servicios = $citaCercana->Servicios ?? $servicios;
                }
            }

            return [
                'idValora' => (int) ($item->idValora ?? 0),
                'idCita' => $item->idCita ? (int) $item->idCita : null,
                'puntuacion' => (int) ($item->puntuacion ?? 0),
                'comentario' => $item->comentario ?? '—',
                'Fecha' => $fechaValoracion ? date('d/m/Y', strtotime($fechaValoracion)) : '—',
                'Fecha_hora' => $fechaValoracion,
                'fecha_valoracion' => $fechaValoracion,
                'Cliente' => $cliente ?: '—',
                'Barbero' => $item->Barbero ?? '',
                'Servicios' => $servicios ?: '—',
            ];
        });

        $total = $lista->count();
        $promedio = $total > 0 ? round($lista->avg('puntuacion'), 2) : null;

        return response()->json([
            'data' => $lista,
            'resumen' => [
                'total' => $total,
                'promedio' => $promedio,
            ],
        ]);
    }

    //
    public function index()
    {
        //hola
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

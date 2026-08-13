<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Citas;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CitasController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if (!$user || !$user->roles) {
            return response()->json(['message' => 'Rol no definido'], 403);
        }

        
        $query = Citas::with(['usuario', 'servicios']);

        if ($user->roles->Nombre_rol === 'Administrador') {
            $citas = $query->get();
            return response()->json($citas, 200);
        }

        if ($user->roles->Nombre_rol === 'Cliente') {
            $citas = $query->where('Usuario_idUsuarioCli', $user->idUsuario)->get();
            return response()->json($citas, 200);
        }

        return response()->json(['message' => 'sin permisos'], 403);
    }

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'Fecha_hora' => 'required|date',
            'estado' => 'required|in:Confirmado,Pendiente,Cancelado',
            'Usuario_idUsuarioBar' => 'required|exists:usuario,idUsuario',
            'servicios' => 'required|array|min:1', // Exigir array
            'servicios.*' => 'exists:servicio,idServicio',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Error de validación', 'errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        if (!$user || !$user->roles) {
            return response()->json(['message' => 'Rol no definido'], 403);
        }


        $dataCita = [
            'Fecha_hora' => $request->input('Fecha_hora'),
            'estado' => $request->input('estado'),
            'Usuario_idUsuarioBar' => $request->input('Usuario_idUsuarioBar'),
            'Usuario_idUsuarioCli' => $user->roles->Nombre_rol === 'Cliente'
                ? $user->idUsuario
                : $request->input('Usuario_idUsuarioCli', $user->idUsuario),
        ];

        try {
            DB::beginTransaction();

            $cita = Citas::create($dataCita);


            $cita->servicios()->attach($request->input('servicios'));

            DB::commit();

            return response()->json($cita->load('servicios'), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error crítico al agendar', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'Fecha_hora' => 'sometimes|required|date',
            'estado' => 'sometimes|required|in:Confirmado,Pendiente,Cancelado',
            'Valora_Idvalora' => 'sometimes|exists:valora,idValora,default:null',
            'Usuario_idUsuarioCli' => 'sometimes|required|exists:usuario,idUsuario',
            'Usuario_idUsuarioBar' => 'sometimes|required|exists:usuario,idUsuario',
            'Servicio_idServicio' => 'sometimes|required|exists:servicio,idServicio',
        ], [
            'Fecha_hora.required' => 'La fecha y hora es obligatoria',
            'Fecha_hora.date' => 'La fecha y hora debe ser una fecha válida',
            'estado.required' => 'El estado es obligatorio',
            'estado.enum' => 'El estado debe ser uno de los siguientes valores: Confirmado, Pendiente, Cancelado',
            'Valora_Idvalora.exists' => 'El valor ingresado no existe en la tabla valora',
            'Usuario_idUsuarioCli.required' => 'El ID del usuario cliente es obligatorio',
            'Usuario_idUsuarioCli.exists' => 'El ID del usuario cliente no existe en la tabla usuario',
            'Usuario_idUsuarioBar.required' => 'El ID del usuario barbero es obligatorio',
            'Usuario_idUsuarioBar.exists' => 'El ID del usuario barbero no existe en la tabla usuario',
            'Servicio_idServicio.required' => 'El ID del servicio es obligatorio',
            'Servicio_idServicio.exists' => 'El ID del servicio no existe en la tabla servicio',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Error de validación', 'errors' => $validator->errors()], 422);
        }


        $user = Auth::user();

        if (!$user || !$user->roles) {
            return response()->json(['message' => 'Rol no definido'], 403);
        }

        $cita = Citas::find($id);
        if (!$cita) {
            return response()->json(['message' => 'Cita no encontrada'], 404);
        }

        if ($user->roles->Nombre_rol == 'Administrador') {
            $cita->update($request->all());
            return response()->json(['message' => 'Cita actualizada correctamente', 'cita' => $cita]);
        }

        if ($user->roles->Nombre_rol === 'Cliente') {
            if ($cita->Usuario_idUsuarioCli !== $user->idUsuario) {
                return response()->json(['message' => 'No tienes permiso para actualizar esta cita'], 403);
            }

            $cita->update($request->only([
                'Fecha_hora',
                'estado',
                'barbero_idbarbero'
            ]));

            return response()->json($cita);
        }

        return response()->json(['message' => 'sin permisos'], 403);
    }

    public function destroy(string $id)
    {
        $user = Auth::user();

        if (!$user || !$user->roles) {
            return response()->json(['message' => 'Rol no definido'], 403);
        }

        $cita = Citas::find($id);
        if (!$cita) {
            return response()->json(['message' => 'Cita no encontrada'], 404);
        }

        if ($user->roles->Nombre_rol === 'Cliente' && $cita->Usuario_idUsuarioCli !== $user->idUsuario) {
            return response()->json(['message' => 'No tienes permiso para eliminar esta cita'], 403);
        }

        $cita->delete();
        return response()->json(['message' => 'Cita eliminada']);
    }
}
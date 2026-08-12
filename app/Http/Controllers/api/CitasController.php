<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Citas;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class CitasController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if (!$user || !$user->roles) {
            return response()->json(['message' => 'Rol no definido'], 403);
        }

        if ($user->roles->Nombre_rol === 'Administrador') {
            $citas = Citas::with('usuario')->get();
            return response()->json($citas);
        }

        if ($user->roles->Nombre_rol === 'Cliente') {
            $citas = Citas::with('usuario')->where('Usuario_idUsuarioCli', $user->idUsuario)->get();
            return response()->json($citas);
        }

        return response()->json(['message' => 'sin permisos'], 403);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'Fecha_hora' => 'required|date',
            'estado' => 'required|in:Confirmado,Pendiente,Cancelado',
            'Valora_Idvalora' => 'exists:valora,idValora,default:null',
            'Usuario_idUsuarioCli' => 'required|exists:usuario,idUsuario',
            'Usuario_idUsuarioBar' => 'required|exists:usuario,idUsuario',
            'Servicio_idServicio' => 'required|exists:servicio,idServicio',
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

        if ($user->roles->Nombre_rol === 'Administrador') {
            $citas = Citas::create($request->all());
            return response()->json($citas);
        }

        if ($user->roles->Nombre_rol === 'Cliente') {
            $request->merge(['Usuario_idUsuarioCli' => $user->idUsuario]);
            $citas = Citas::create($request->all());
            return response()->json($citas);
        }

        return response()->json(['message' => 'sin permisos'], 403);

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
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

       
        $query = Citas::with(['cliente', 'barbero', 'servicios']);

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
            'Valora_Idvalora' => 'sometimes|nullable|exists:valora,Idvalora',
            'Usuario_idUsuarioCli' => 'sometimes|required|exists:usuario,idUsuario',
            'Usuario_idUsuarioBar' => 'sometimes|required|exists:usuario,idUsuario',
            'servicios' => 'sometimes|required|array|min:1',
            'servicios.*' => 'exists:servicio,idServicio',
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


        if ($user->roles->Nombre_rol === 'Cliente' && $cita->Usuario_idUsuarioCli !== $user->idUsuario) {
            return response()->json(['message' => 'No tienes permiso para actualizar esta cita'], 403);
        }

        try {
            DB::beginTransaction();

            if ($user->roles->Nombre_rol === 'Administrador') {

                $cita->update($request->except(['servicios']));
            } elseif ($user->roles->Nombre_rol === 'Cliente') {

                $cita->update($request->only([
                    'Fecha_hora',
                    'estado',
                    'Usuario_idUsuarioBar'
                ]));
            }

            // 2. Ejecutar la sincronización N:M en la tabla pivote de manera segura
            if ($request->has('servicios')) {
                $cita->servicios()->sync($request->input('servicios'));
            }

            DB::commit();

            return response()->json([
                'message' => 'Cita actualizada correctamente',
                'cita' => $cita->load('servicios')
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error crítico al actualizar', 'error' => $e->getMessage()], 500);
        }
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
<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class usuarioController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $user = $request->user();

        if (! $user || ! $user->roles) {
            return response()->json(['result' => 'error', 'message' => 'Rol no definido'], 403);
        }

        if ($user->roles->Nombre_rol === 'Administrador') {
            return response()->json(['result' => 'ok', 'data' => usuario::with(['especialidad', 'horario'])->get()]);
        }

        if ($user->roles->Nombre_rol === 'Cliente') {

            $clientes = usuario::where('idUsuario', $user->idUsuario)->get();
            $barberos = usuario::with(['especialidad', 'horario'])
                ->where('Roles_IDRol', 2)
                ->get();

            return response()->json([
                'result' => 'ok',
                'data' => [
                    'clientes' => $clientes,
                    'barberos' => $barberos,
                ],
            ]);

        }
        if ($user->roles->Nombre_rol === 'Barbero') {
            $barbero = usuario::with(['especialidad', 'horario'])
                ->where('idUsuario', $user->idUsuario)
                ->get();

            return response()->json([
                'result' => 'ok',
                'data' => [
                    'barbero' => $barbero,
                ],
            ]);
        } else {
            return response()->json(['result' => 'error', 'message' => 'sin permisos'], 403);
        }

    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'Nombre' => 'required|string',
            'correo' => 'required|string|max:255|unique:usuario,correo',
            'telefono' => 'required|string|max:45',
            'contrasenha' => 'required|string|min:6',
            'Roles_IDRol' => 'required|exists:roles,iDRol',
            'servicios' => 'sometimes|array',
            'servicios.*' => 'exists:servicio,idServicio',
            'horarios' => 'sometimes|array',
            'horarios.*' => 'exists:horario,idhorario',
        ]);

        if ($validator->fails()) {
            return response()->json(['result' => 'error', 'message' => 'Error de validación', 'errors' => $validator->errors()], 422);
        }

        $datos = $validator->validated();
        $datos['contrasenha'] = bcrypt($datos['contrasenha']);
        $servicios = $datos['servicios'] ?? [];
        $horarios = $datos['horarios'] ?? [];
        unset($datos['servicios'], $datos['horarios']);

        if ((int) $datos['Roles_IDRol'] === 2) {
            $datos['especialidad'] = implode(',', $servicios);
            $datos['horario'] = implode(',', $horarios);
        }

        $usuario = DB::transaction(function () use ($datos, $servicios, $horarios) {
            $usuario = usuario::create($datos);

            if ($usuario->Roles_IDRol == 2) {
                $usuario->especialidad()->sync($servicios);
                $usuario->horario()->sync($horarios);
            }

            return $usuario;
        });

        return response()->json($usuario->load(['especialidad', 'horario']), 201);
    }

    public function show(string $id)
    {
        $usuario = usuario::find($id);

        if (! $usuario) {
            return response()->json(['result' => 'error', 'message' => 'Usuario no encontrado'], 404);
        }
        if ($usuario->roles->Nombre_rol === 'Administrador') {
            return response()->json(['result' => 'ok', 'data' => $usuario]);
        }

    }

    public function update(Request $request, string $id)
    {
        $usuario = usuario::find($id);

        if (! $usuario) {
            return response()->json(['result' => 'error', 'message' => 'Usuario no encontrado'], 404);
        }

        $usuarioAutenticado = $request->user();

        $validator = Validator::make($request->all(), [
            'Nombre' => 'required|string',
            'correo' => 'sometimes|required|string|max:255|unique:usuario,correo,'.$usuario->idUsuario.',idUsuario',
            'telefono' => 'required|string|max:45',
            'contrasenha' => 'sometimes|string|min:6',
            'Roles_IDRol' => 'sometimes|required|exists:roles,iDRol',

            'servicios' => 'sometimes|array',
            'servicios.*' => 'exists:servicio,idServicio',
            'horarios' => 'sometimes|array',
            'horarios.*' => 'exists:horario,idhorario',
        ], [
            'Nombre.required' => 'El campo Nombre es obligatorio.',
            'correo.required' => 'El campo correo es obligatorio.',
            'correo.unique' => 'El correo ya está en uso.',
            'telefono.required' => 'El campo telefono es obligatorio.',
            'contrasenha.min' => 'La contrasenha debe tener al menos 6 caracteres.',
            'Roles_IDRol.exists' => 'El rol seleccionado no es válido.',
        ]);

        if ($validator->fails()) {
            return response()->json(['result' => 'error', 'message' => 'Error de validación', 'errors' => $validator->errors()], 422);
        }

        if ($request->has('servicios') && $usuarioAutenticado->Roles_IDRol !== 1) {
            return response()->json([
                'message' => 'Acceso denegado. Solo un administrador puede habilitar',
            ], 403);
        }

        $datos = $validator->validated();
        $servicios = $datos['servicios'] ?? null;
        $horarios = $datos['horarios'] ?? null;
        unset($datos['servicios'], $datos['horarios']);

        if ($request->filled('contrasenha')) {
            $datos['contrasenha'] = bcrypt($datos['contrasenha']);
        } else {
            unset($datos['contrasenha']);
        }

        if ($usuarioAutenticado->Roles_IDRol !== 1) {
            unset($datos['Roles_IDRol']);
        }

        DB::transaction(function () use ($usuario, $datos, $servicios, $horarios) {
            $usuario->update($datos);

            if ($usuario->Roles_IDRol == 2) {
                if ($servicios !== null) {
                    $usuario->especialidad()->sync($servicios);
                }

                if ($horarios !== null) {
                    $usuario->horario()->sync($horarios);
                }
            }
        });

        return response()->json(['result' => 'ok', 'message' => 'Usuario actualizado correctamente', 'usuario' => $usuario->load(['especialidad', 'horario'])]);
    }

    public function destroy(string $id)
    {
        $usuario = usuario::find($id);

        if (! $usuario) {
            return response()->json(['result' => 'error', 'message' => 'Usuario no encontrado'], 404);
        }

        $usuario->delete();

        return response()->json(['result' => 'ok', 'message' => 'Usuario eliminado correctamente']);

    }
}

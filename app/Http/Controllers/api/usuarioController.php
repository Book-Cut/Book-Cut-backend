<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class usuarioController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $user = $request->user();

        if (!$user || !$user->roles) {
            return response()->json(['result' => 'error', 'message' => 'Rol no definido'], 403);
        }

        if ($user->roles->Nombre_rol === 'Administrador') {
            return response()->json(['result' => 'ok', 'data' => usuario::all()]);
        }

        if ($user->roles->Nombre_rol === 'Cliente') {

            $barberos = usuario::where('roles_idRol', 2)->get(['Nombre', 'Especialidad', 'Horario']);

            return response()->json([
                'result' => 'ok',
                'data' => [
                    'barberos' => $barberos
                ]
            ]);


        }
        if ($user->roles->Nombre_rol === 'Barbero') {
            $barbero = usuario::where('idUsuario', $user->idUsuario)->get(['Nombre', 'Especialidad', 'Horario']);
            return response()->json([
                'result' => 'ok',
                'data' => [
                    'barbero' => $barbero
                ]
            ]);
        } else {
            return response()->json(['result' => 'error', 'message' => 'sin permisos'], 403);
        }



    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'Nombre' => 'required|string',
            'correo' => 'required|string|max:255|unique:usuario,Correo',
            'telefono' => 'required|string|max:45',
            'contrasenha' => 'required|string|min:6',
            'Roles_IDRol' => 'required|exists:roles,idRol',
             'servicio' => 'sometimes|array'
        ], [
            'Nombre.required' => 'El campo Nombre es obligatorio.',
            'correo.required' => 'El campo correo es obligatorio.',
            'correo.unique' => 'El correo ya está en uso.',
            'telefono.required' => 'El campo telefono es obligatorio.',
            'contrasenha.required' => 'El campo contrasenha es obligatorio.',
            'contrasenha.min' => 'La contrasenha debe tener al menos 6 caracteres.',
            'Roles_IDRol.required' => 'El campo Roles_IDRol es obligatorio.',
            'Roles_IDRol.exists' => 'El rol seleccionado no es válido.',
            'terminos_aceptados.required' => 'Debes aceptar los términos y condiciones.',
            'terminos_aceptados.accepted' => 'Debes aceptar los términos y condiciones.',
            'servicio.*' => 'exists:servicio,idServicio'
        ]);

        if ($validator->fails()) {
            return response()->json(['result' => 'error', 'message' => 'Error de validación', 'errors' => $validator->errors()], 422);
        }

        $datos = $request->all();
        $datos['contrasenha'] = bcrypt($datos['contrasenha']);

        $usuario = usuario::create($datos);


        if ($usuario->Roles_IDRol == 2 && $request->has('servicios')) {
            $usuario->especialidades()->attach($request->servicios);
        }

        return response()->json($usuario->load('especialidades'), 201);




        $usuario = usuario::create($request->all());

        return response()->json($usuario, 201);

        if (!$user || !$user->roles) {
            return response()->json(['result' => 'error', 'message' => 'Rol no definido'], 403);
        }
        if ($user->roles->Nombre_rol === 'Administrador') {
            $usuarios = usuario::create($request->all());
            return response()->json(['result' => 'ok', 'data' => $usuarios], 201);
        }
        if ($user->roles->Nombre_rol === 'Cliente') {
            $request->merge(['idUsuario' => $user->idUsuario]);
            $usuarioCli = usuario::where('idUsuario', $user->idUsuario)->get();
            return response()->json(['result' => 'ok', 'data' => $usuarioCli], 201);
        }

        if ($user->roles->Nombre_rol === 'Barbero') {
            $request->merge(['idUsuario' => $user->idUsuario]);
            $usuarioBar = usuario::where('idUsuario', $user->idUsuario)->get();
            return response()->json($usuarioBar, 201);
        }

        return response()->json($usuario->load('especialidad'), 201);
    }

    public function show(string $id)
    {
        $usuario = usuario::find($id);

        if (!$usuario) {
            return response()->json(['result' => 'error', 'message' => 'Usuario no encontrado'], 404);
        }
        if ($usuario->roles->Nombre_rol === 'Administrador') {
            return response()->json(['result' => 'ok', 'data' => $usuario]);
        }


    }

    public function update(Request $request, string $id)
    {
        $usuario = usuario::find($id);

        if (!$usuario) {
            return response()->json(['result' => 'error', 'message' => 'Usuario no encontrado'], 404);
        }


        $usuarioAutenticado = $request->user();

        $validator = Validator::make($request->all(), [
            'Nombre' => 'required|string',
            'correo' => 'sometimes|required|string|max:255|unique:usuario,Correo,' . $usuario->idUsuario . ',idUsuario',
            'telefono' => 'required|string|max:45',
            'contrasenha' => 'sometimes|string|min:6',
            'Roles_IDRol' => 'sometimes|required|exists:roles,idRol',

            'servicios' => 'sometimes|array',
            'servicios.*' => 'exists:servicios,idServicio'
        ], [
            'Nombre.required' => 'El campo Nombre es obligatorio.',
            'correo.required' => 'El campo correo es obligatorio.',
            'correo.unique' => 'El correo ya está en uso.',
            'telefono.required' => 'El campo telefono es obligatorio.',
            'contrasenha.min' => 'La contrasenha debe tener al menos 6 caracteres.',
            'Roles_IDRol.exists' => 'El rol seleccionado no es válido.'
        ]);

        if ($validator->fails()) {
            return response()->json(['result' => 'error', 'message' => 'Error de validación', 'errors' => $validator->errors()], 422);
        }


        if ($request->has('servicio') && $usuarioAutenticado->Roles_IDRol !== 1) {
            return response()->json([
                'message' => 'Acceso denegado. Solo un administrador puede habilitar'
            ], 403);
        }

        $datos = $request->all();

        if ($request->filled('contrasenha')) {
            $datos['contrasenha'] = bcrypt($datos['contrasenha']);
        } else {
            unset($datos['contrasenha']);
        }


        if ($usuarioAutenticado->Roles_IDRol !== 1) {
            unset($datos['Roles_IDRol']);
        }

        $usuario->update($datos);


        if ($usuario->Roles_IDRol == 2 && $request->has('servicio')) {
            $usuario->especialidad()->sync($request->servicio);
        }

        return response()->json(['result' => 'ok', 'message' => 'Usuario actualizado correctamente', 'usuario' => $usuario->load('especialidades')]);
    }

    public function destroy(string $id)
    {
        $usuario = usuario::find($id);

        if (!$usuario) {
            return response()->json(['result' => 'error', 'message' => 'Usuario no encontrado'], 404);
        }

        $usuario->delete();
        return response()->json(['result' => 'ok', 'message' => 'Usuario eliminado correctamente']);


    }

}

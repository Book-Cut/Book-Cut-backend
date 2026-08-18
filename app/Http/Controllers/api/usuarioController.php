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
            return response()->json(['message' => 'Rol no definido'], 403);
        }

        if ($user->roles->Nombre_rol === 'Administrador') {
            return response()->json(usuario::all());
        }

        if ($user->roles->Nombre_rol === 'Cliente') {
            //return response()->json(usuario::where('idUsuario', $user->idUsuario)->get());

            #nombre, espcialidad y horario de los barberos, y el nombre del cliente que agendo la cita

            //if ($user->roles->Nombre_rol === 'Cliente') {
            $barberos = usuario::where('roles_idRol', 2)->get(['Nombre', 'Especialidad', 'Horario']);
            // $citas = $user->citas()->with('getBarbero:idUsuario,Nombre')->get();
            return response()->json([
                'result' => 'ok',
                'data' => [
                    'barberos' => $barberos//,
                    //  'citas' => $citas
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

            'servicios' => 'sometimes|array',
            'servicios.*' => 'exists:servicios,idServicio'
        ], [
            'Nombre.required' => 'El campo Nombre es obligatorio.',
            'correo.required' => 'El campo correo es obligatorio.',
            'correo.unique' => 'El correo ya está en uso.',
            'telefono.required' => 'El campo telefono es obligatorio.',
            'contrasenha.required' => 'El campo contrasenha es obligatorio.',
            'contrasenha.min' => 'La contrasenha debe tener al menos 6 caracteres.',
            'Roles_IDRol.required' => 'El campo Roles_IDRol es obligatorio.',
            'Roles_IDRol.exists' => 'El rol seleccionado no es válido.'
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Error de validación', 'errors' => $validator->errors()], 422);
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
            return response()->json(['message' => 'Rol no definido'], 403);
        }
        if ($user->roles->Nombre_rol === 'Administrador') {
            $usuarios = usuario::create($request->all());
            return response()->json($usuarios, 201);
        }
        if ($user->roles->Nombre_rol === 'Cliente') {
            $request->merge(['idUsuario' => $user->idUsuario]);
            $usuarioCli = usuario::where('idUsuario', $user->idUsuario)->get();
            return response()->json($usuarioCli, 201);
        }

        if ($user->roles->Nombre_rol === 'Barbero') {
            $request->merge(['idUsuario' => $user->idUsuario]);
            $usuarioBar = usuario::where('idUsuario', $user->idUsuario)->get();
            return response()->json($usuarioBar, 201);
        }

    }

    public function show(string $id)
    {
        $usuario = usuario::find($id);

        if (!$usuario) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }
        if ($usuario->roles->Nombre_rol === 'Administrador') {
            return response()->json($usuario);
        }


    }

    public function update(Request $request, string $id)
    {
        $usuario = usuario::find($id);

        if (!$usuario) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        $validator = Validator::make($request->all(), [
            'Nombre' => 'required|string',
            'correo' => 'sometimes|required|string|max:255|unique:usuario,Correo,' . $usuario->idUsuario . ',idUsuario',
            'telefono' => 'required|string|max:45',
            'contrasenha' => 'sometimes|string|min:6',
            'Roles_IDRol' => 'sometimes|required|exists:roles,idRol',

            'servicios' => 'sometimes|array',
            'servicios.*' => 'exists:servicios,idServicio'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $datos = $request->all();


        if ($request->filled('contrasenha')) {
            $datos['contrasenha'] = bcrypt($datos['contrasenha']);
        } else {
            unset($datos['contrasenha']);
        }


        $usuario->update($datos);


        if ($usuario->Roles_IDRol == 2 && $request->has('servicios')) {

            $usuario->especialidades()->sync($request->servicios);
        }

        return response()->json([
            'message' => 'Usuario actualizado correctamente',
            'usuario' => $usuario->load('especialidades')
        ]);
    }

    public function destroy(string $id)
    {
        $usuario = usuario::find($id);

        if (!$usuario) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        $usuario->delete();
        return response()->json(['message' => 'Usuario eliminado correctamente']);


    }

}

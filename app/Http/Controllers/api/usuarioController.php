<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\usuario;
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

        return response()->json(usuario::where('idUsuario', $user->idUsuario)->get());
    }

    public function store(Request $request)
    {
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
        $user = Auth::user();
        $user = usuario::find($id);


        if ($user->roles->Nombre_rol === 'Administrador') {
            $user->update($request->all());
            return response()->json(['message' => 'Usuario actualizado correctamente', 'usuario' => $user]);
        } else {
            return response()->json(['message' => 'No tienes permisos para actualizar este usuario'], 403);
        }

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

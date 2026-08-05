<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class usuarioController extends Controller
{

    public function index()
    {
        $usuarios = usuario::all();
        return response()->json($usuarios);
    }

    public function store(Request $request)
    {

        $usuario = usuario::create($request->all());
        return response()->json($usuario, 201);
    }

    public function show(string $id)
    {
        $usuario = usuario::find($id);

        if (!$usuario) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        return response()->json($usuario);
    }

    public function update(Request $request, string $id)
    {
        $user = Auth::user();
        $usuario = usuario::find($id);

        //return response()->json(['user' => $user, 'usuario' => $usuario, 'request' => $request->all()]);

        if (!$usuario) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        if ($user->roles->Nombre_rol == 'Administrador') {
            $usuario->update($request->all());
            return response()->json($usuario);
        }

        return response()->json(['message' => 'No autorizado'], 403);
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

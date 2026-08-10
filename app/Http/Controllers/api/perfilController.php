<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\perfil;
use Illuminate\Support\Facades\Auth;

class perfilController extends Controller
{
    public function index()
    {
        $perfil = perfil::all();
        return response()->json($perfil);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        if (!$user || !$user->roles) {
            return response()->json(['message' => 'Rol no definido'], 403);
        }

        if ($user->roles->Nombre_rol !== 'Administrador') {
            return response()->json([
                'message' => 'No tienes permisos para crear perfiles.'
            ], 403);
        }

        $request->validate([
            'Ranking' => 'nullable|numeric',
            'foto_perfil' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'Usuario_idUsuario' => 'required|numeric|exists:usuario,idUsuario',
        ]);

        $data = $request->only(['Ranking', 'Usuario_idUsuario']);

        if ($request->hasFile('foto_perfil')) {
            $path = $request->file('foto_perfil')->store('perfiles', 'public');
            $data['foto_perfil'] = $path;
        }

        $perfil = perfil::create($data);

        return response()->json([
            'message' => 'Perfil creado correctamente',
            'perfil' => $perfil
        ], 201);
    }

    public function show(string $id)
    {
        $perfil = perfil::find($id);
        if ($perfil) {
            return response()->json($perfil);
        }
        return response()->json(['message' => 'Perfil no encontrado'], 404);
    }

    public function update(Request $request, string $id)
    {
        $user = Auth::user();

        if (!$user || !$user->roles) {
            return response()->json(['message' => 'Rol no definido'], 403);
        }

        if ($user->roles->Nombre_rol !== 'Administrador') {
            return response()->json([
                'message' => 'No tienes permisos para actualizar perfiles.'
            ], 403);
        }

        $perfil = perfil::find($id);
        if (!$perfil) {
            return response()->json(['message' => 'Perfil no encontrado'], 404);
        }

        $request->validate([
            'Ranking' => 'nullable|integer',
            'foto_perfil' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'Usuario_idUsuario' => 'nullable|integer|exists:usuario,idUsuario',
        ]);

        $data = array_filter($request->only(['Ranking', 'Usuario_idUsuario']));

        if ($request->hasFile('foto_perfil')) {
            $path = $request->file('foto_perfil')->store('perfiles', 'public');
            $data['foto_perfil'] = $path;
        }

        $perfil->update($data);
        return response()->json(['message' => 'Perfil actualizado correctamente', 'perfil' => $perfil]);
    }

    public function destroy(string $id)
    {
        $user = Auth::user();

        if (!$user || !$user->roles) {
            return response()->json(['message' => 'Rol no definido'], 403);
        }

        if ($user->roles->Nombre_rol !== 'Administrador') {
            return response()->json([
                'message' => 'No tienes permisos para eliminar perfiles.'
            ], 403);
        }

        $perfil = perfil::find($id);
        if ($perfil) {
            $perfil->delete();
            return response()->json(['message' => 'Perfil eliminado']);
        } else {
            return response()->json(['message' => 'Perfil no encontrado'], 404);
        }
    }
}

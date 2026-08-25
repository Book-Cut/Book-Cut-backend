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
        return response()->json(['result' => 'ok', 'message' => 'Perfiles obtenidos correctamente', 'data' => $perfil]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        if (!$user || !$user->roles) {
            return response()->json(['result' => 'error', 'message' => 'Rol no definido'], 403);
        }

        if ($user->roles->Nombre_rol !== 'Administrador') {
            return response()->json([
                'result' => 'error',
                'message' => 'No tienes permisos para crear perfiles.'
            ], 403);
        }

        $request->validate([
            'Ranking' => 'nullable|numeric',
            'foto_perfil' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'Usuario_idUsuario' => 'required|numeric|exists:usuario,idUsuario',
        ], [
            'Ranking.numeric' => 'El campo Ranking debe ser un número.',
            'foto_perfil.image' => 'El archivo debe ser una imagen.',
            'foto_perfil.mimes' => 'La imagen debe ser de tipo jpg, jpeg o png.',
            'foto_perfil.max' => 'La imagen no debe superar los 2MB.',
            'Usuario_idUsuario.required' => 'El campo Usuario_idUsuario es obligatorio.',
            'Usuario_idUsuario.numeric' => 'El campo Usuario_idUsuario debe ser un número.',
            'Usuario_idUsuario.exists' => 'El usuario especificado no existe.',
        ]);

        $data = $request->only(['Ranking', 'Usuario_idUsuario']);

        if ($request->hasFile('foto_perfil')) {
            $path = $request->file('foto_perfil')->store('perfiles', 'public');
            $data['foto_perfil'] = $path;
        }

        $perfil = perfil::create($data);

        return response()->json([
            'result' => 'ok',
            'message' => 'Perfil creado correctamente',
            'data' => $perfil,
            'foto_url' => $perfil->foto_perfil ? asset('storage/' . $perfil->foto_perfil) : null
        ], 201);
    }

    public function show(string $id)
    {
        $perfil = perfil::find($id);
        if ($perfil) {
            return response()->json($perfil);
        }
        return response()->json(['result' => 'error', 'message' => 'Perfil no encontrado'], 404);
    }

    public function update(Request $request, string $id)
    {
        $user = Auth::user();

        if (!$user || !$user->roles) {
            return response()->json(['result' => 'error', 'message' => 'Rol no definido'], 403);
        }

        if ($user->roles->Nombre_rol !== 'Administrador') {
            return response()->json([
                'result' => 'error',
                'message' => 'No tienes permisos para actualizar perfiles.'
            ], 403);
        }

        $perfil = perfil::find($id);
        if (!$perfil) {
            return response()->json(['result' => 'error', 'message' => 'Perfil no encontrado'], 404);
        }

        $request->validate([
            'Ranking' => 'nullable|integer',
            'foto_perfil' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'Usuario_idUsuario' => 'nullable|integer|exists:usuario,idUsuario',
        ], [
            'Ranking.numeric' => 'El campo Ranking debe ser un número.',
            'foto_perfil.image' => 'El archivo debe ser una imagen.',
            'foto_perfil.mimes' => 'La imagen debe ser de tipo jpg, jpeg o png.',
            'foto_perfil.max' => 'La imagen no debe superar los 2MB.',
            'Usuario_idUsuario.exists' => 'El usuario especificado no existe.',
        ]);

        $data = array_filter($request->only(['Ranking', 'Usuario_idUsuario']));

        if ($request->hasFile('foto_perfil')) {
            $path = $request->file('foto_perfil')->store('perfiles', 'public');
            $data['foto_perfil'] = $path;
        }

        $perfil->update($data);
        return response()->json([
            'result' => 'ok',
            'message' => 'Perfil actualizado correctamente',
            'data' => $perfil,
            'foto_url' => $perfil->foto_perfil ? asset('storage/' . $perfil->foto_perfil) : null
        ]);
    }

    public function destroy(string $id)
    {
        $user = Auth::user();

        if (!$user || !$user->roles) {
            return response()->json(['result' => 'error', 'message' => 'Rol no definido'], 403);
        }

        if ($user->roles->Nombre_rol !== 'Administrador') {
            return response()->json([
                'result' => 'error',
                'message' => 'No tienes permisos para eliminar perfiles.'
            ], 403);
        }

        $perfil = perfil::find($id);
        if ($perfil) {
            $perfil->delete();
            return response()->json(['result' => 'ok', 'message' => 'Perfil eliminado']);
        } else {
            return response()->json(['result' => 'error', 'message' => 'Perfil no encontrado'], 404);
        }
    }
}    



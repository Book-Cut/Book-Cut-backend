<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\perfil;

class perfilController extends Controller
{
    public function index()
    {
        $perfil = perfil::all();
        return response()->json($perfil);
    }

    public function store(Request $request)
{
    $request->validate([
        'Ranking' => 'nullable|numeric',
        'foto_perfil' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        'Usuario_idUsuario' => 'required|numeric|exists:Usuario,idUsuario',
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
        $perfil = perfil::find($id);
        if (!$perfil) {
            return response()->json(['message' => 'Perfil no encontrado'], 404);
        }

        $request->validate([
            'Ranking' => 'nullable|integer',
            'foto_perfil' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'Usuario_idUsuario' => 'nullable|integer|exists:Usuario,idUsuario',
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
        $perfil = perfil::find($id);
        if ($perfil) {
            $perfil->delete();
            return response()->json(['message' => 'Perfil eliminado']);
        }
        return response()->json(['message' => 'Perfil no encontrado'], 404);
    }
}
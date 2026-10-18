<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PerfilController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->datosPerfil($request->user()),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $usuario = $request->user();
        $datos = $request->validate([
            'Nombre' => ['required', 'string', 'max:255'],
            'correo' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('usuario', 'correo')->ignore($usuario->idUsuario, 'idUsuario'),
            ],
            'telefono' => ['required', 'string', 'max:45'],
            'contrasenha' => ['nullable', 'string', 'min:6'],
        ]);

        $usuario->Nombre = $datos['Nombre'];
        $usuario->correo = $datos['correo'];
        $usuario->telefono = $datos['telefono'];

        if (! empty($datos['contrasenha'])) {
            $usuario->contrasenha = Hash::make($datos['contrasenha']);
        }

        $usuario->save();

        return response()->json([
            'message' => 'Perfil actualizado correctamente.',
            'data' => $this->datosPerfil($usuario),
        ]);
    }

    private function datosPerfil(usuario $usuario): array
    {
        $foto = $usuario->foto_perfil;

        return [
            'idUsuario' => $usuario->idUsuario,
            'Nombre' => $usuario->Nombre,
            'correo' => $usuario->correo,
            'telefono' => $usuario->telefono,
            'foto_perfil' => $foto && ! str_starts_with($foto, 'http')
                ? Storage::disk('public')->url($foto)
                : $foto,
        ];
    }
}

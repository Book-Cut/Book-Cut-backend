<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\usuario;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{

    public function register(Request $request)
    {
        $request->validate([
            'Nombre' => 'required|string|max:255',
            'correo' => 'required|string|email|max:255|unique:Usuario,correo',
            'telefono' => 'required|string|max:45',
            'contrasenha' => 'required|string|min:6',
        ]);

        $user = usuario::create([
            'Nombre' => $request->Nombre,
            'correo' => $request->correo,
            'telefono' => $request->telefono,
            'contrasenha' => Hash::make($request->contrasenha),
            'Roles_IDRol' => 3,
        ]);

        $token = $user->createToken('book_cut_api')->plainTextToken;

        return response()->json([
            'result' => 'ok',
            'message' => 'Usuario registrado exitosamente',
            'user' => $user,
            'token' => $token
        ], 201);
    }

    public function login(Request $request)
    {

        $request->validate([
            'correo' => 'required|string|email',
            'contrasenha' => 'required|string',
        ]);

        $user = usuario::where('correo', $request->correo)->first();

        if (!$user) {
            return response()->json(['result' => 'error', 'message' => 'Credenciales inválidas'], 401);
        }

        if ($request->contrasenha !== $user->contrasenha && !Hash::check($request->contrasenha, $user->contrasenha)) {
            return response()->json(['result' => 'error', 'message' => 'Credenciales inválidas'], 401);
        }

        if ($user->tokens()->exists() && $user->tokens()->where('name', 'book_cut_api')->exists()) {
            $user->tokens()->where('name', 'book_cut_api')->delete();
        }

        $token = $user->createToken('book_cut_api')->plainTextToken;

        return response()->json([
            'result' => 'ok',
            'message' => 'Usuario autenticado exitosamente',
            'user' => $user,
            'token' => $token
        ], 200);
    }

    public function logout(Request $request)
    {

        $request->user()->currentAccessToken()->delete();

        return response()->json(['result' => 'ok', 'message' => 'Sesión cerrada exitosamente'], 200);
    }
}
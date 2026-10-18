<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\usuario;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

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

    public function forgotPassword(Request $request)
    {
        $request->validate(['correo' => 'required|email']);

        $user = usuario::where('correo', $request->correo)->first();

        if ($user) {
            $token = Str::random(64);
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user->correo],
                ['token' => Hash::make($token), 'created_at' => now()]
            );

            $url = rtrim(env('FRONTEND_URL', 'http://localhost:5173'), '/'). '/restablecer?token=' . $token . '&correo=' . urlencode($user->correo);

            Mail::raw("Hola {$user->Nombre},\n\nRestablece tu contraseña aquí (válido 60 min):\n$url\n\nSi no fuiste tú, ignora este correo.", function ($m) use ($user) {
                $m->to($user->correo)->subject('Restablecer contraseña - Book&Cut');
            });
        }

        return response()->json(['result' => 'ok,funciona', 'message' => 'si el correo es existente, se le enviara un enlace']);

    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'correo' => 'required|email',
            'token' => 'required|string',
            'contrasenha' => 'required|string|min:6|confirmed',
        ]);

        $row = DB::table('password_reset_tokens')->where('email', $request->correo)->first();

        if (!$row || !Hash::check($request->token, $row->token)
            || now()->diffInMinutes($row->created_at) > 60) {
            return response()->json(['result' => 'error', 'message' => 'Enlace invalido o ya expiro'], 422);
        }

        $user = usuario::where('correo', $request->correo)->firstOrFail();
        $user->contrasenha = Hash::make($request->contrasenha);
        $user->save();

        DB::table('password_reset_tokens')->where('email', $request->correo)->delete();
        return response()->json(['result' => 'ok', 'message' => 'Contraseña restablecida exitosamente'], 200);
    }
}
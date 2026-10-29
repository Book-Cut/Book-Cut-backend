<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\usuario;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;


class AutenticacionController extends Controller
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

        //$users = usuario::all();

        /*return response()->json([
            'result' => 'ok',
            'message' => 'Usuario autenticado exitosamente',
            'user' => $users
        ], 200);*/

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

            $url = rtrim(config('app.frontend_url'), '/') . '/restablecer?token=' . $token . '&correo=' . urlencode($user->correo);

            Mail::raw("Hola {$user->Nombre},\n\nRestablece tu contraseña aquí (válido 60 min):\n$url\n\nSi no fuiste tú, ignora este correo.", function ($m) use ($user) {
                $m->to($user->correo)->subject('Restablecer contraseña - Book&Cut');
            });
        }

        return response()->json([
            'result' => 'ok',
            'message' => 'Si el correo está registrado, recibirás un enlace para cambiar tu contraseña. Revisa también la carpeta de spam.',
        ]);

    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'correo' => 'required|email',
            'token' => 'required|string',
            'contrasenha' => 'required|string|min:6|confirmed',
        ]);

        $row = DB::table('password_reset_tokens')->where('email', $request->correo)->first();

        if (
            !$row
            || !Hash::check($request->token, $row->token)
            || now()->greaterThan(\Illuminate\Support\Carbon::parse($row->created_at)->addMinutes(60))
        ) {
            return response()->json(['result' => 'error', 'message' => 'Enlace invalido o ya expiro'], 422);
        }

        $user = usuario::where('correo', $request->correo)->firstOrFail();
        $user->contrasenha = Hash::make($request->contrasenha);
        $user->save();

        DB::table('password_reset_tokens')->where('email', $request->correo)->delete();
        return response()->json(['result' => 'ok', 'message' => 'Contraseña restablecida exitosamente'], 200);
    }

    public function sendRegisterCode(Request $request)
    {
        $request->validate([
            'Nombre' => 'required|string|max:255',
            'correo' => 'required|string|email|max:255|unique:Usuario,correo',
            'telefono' => 'required|string|max:45',
            'contrasenha' => 'required|string|min:6',
        ]);

        $codigo = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        Cache::put('registro:' . $request->correo, [
            'codigo' => Hash::make($codigo),
            'intentos' => 0,
            'datos' => [
                'Nombre' => $request->Nombre,
                'correo' => $request->correo,
                'telefono' => $request->telefono,
                'contrasenha' => Hash::make($request->contrasenha),
                'Roles_IDRol' => 3,
                'terminos_aceptados' => $request->boolean('terminos_aceptados'),
            ],
        ], now()->addMinutes(10));

        Mail::send('correos.codigo-verificacion', ['codigo' => $codigo], function ($mensaje) use ($request) {
            $mensaje->to($request->correo)->subject('Verifica tu cuenta - Book&Cut');
        });

        return response()->json(['result' => 'ok', 'message' => 'Codigo enviado']);
    }

    public function verifyRegisterCode(Request $request)
    {
        $request->validate(['correo' => 'required|email', 'codigo' => 'required|digits:6']);

        $key = 'registro:' . $request->correo;
        $registro = Cache::get($key);

        if (!$registro) {
            return response()->json(['result' => 'error', 'message' => 'el codigo expiro, solicite uno nuevo'], 422);
        }

        if ($registro['intentos'] >= 5) {
            Cache::forget($key);
            return response()->json(['result' => 'error', 'message' => 'muchos intentos, solicita otro codigo'], 429);
        }

        if (!Hash::check($request->codigo, $registro['codigo'])) {
            $registro['intentos']++;
            Cache::put($key, $registro, now()->addMinutes(10));
            return response()->json(['result' => 'error', 'message' => 'el codigo es incorrecto'], 422);
        }

        $user = usuario::create($registro['datos']);
        Cache::forget($key);

        return response()->json([
            'result' => 'ok',
            'message' => 'Usuario registrado exitosamente',
            'user' => $user,
            'token' => $user->createToken('book_cut_api')->plainTextToken,
        ], 201);
    }
}

<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\horario;
use App\Models\usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
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
        $esBarbero = $usuario->roles?->Nombre_rol === 'Barbero';
        if (! $esBarbero && ($request->has('servicio_ids') || $request->has('horarios'))) {
            return response()->json(['message' => 'Solo los barberos pueden modificar su especialidad y horarios.'], 403);
        }

        $reglas = [
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
        ];

        if ($esBarbero) {
            $reglas['servicio_ids'] = ['sometimes', 'array'];
            $reglas['servicio_ids.*'] = ['integer', 'exists:servicio,idServicio'];
            $reglas['horarios'] = ['sometimes', 'array', 'max:7'];
            $reglas['horarios.*.diasemana'] = [
                'required',
                'string',
                'distinct',
                Rule::in(['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo']),
            ];
            $reglas['horarios.*.horainicio'] = ['required', 'date_format:H:i'];
            $reglas['horarios.*.horafin'] = ['required', 'date_format:H:i'];
        }

        $validador = Validator::make($request->all(), $reglas);
        $validador->after(function ($validador) use ($request, $esBarbero) {
            if (! $esBarbero || ! is_array($request->input('horarios'))) {
                return;
            }

            foreach ($request->input('horarios') as $indice => $horario) {
                if (
                    isset($horario['horainicio'], $horario['horafin'])
                    && $horario['horainicio'] >= $horario['horafin']
                ) {
                    $validador->errors()->add("horarios.$indice.horafin", 'La hora de fin debe ser posterior a la hora de inicio.');
                }
            }
        });

        $datos = $validador->validate();
        $horarios = $datos['horarios'] ?? null;
        $actualizarServicio = array_key_exists('servicio_ids', $datos);
        $servicioIds = collect($datos['servicio_ids'] ?? [])->flatten()->map('intval')->unique()->values()->all();
        unset($datos['horarios'], $datos['servicio_ids']);

        DB::transaction(function () use ($usuario, $datos, $horarios, $servicioIds, $actualizarServicio) {
            $usuario->Nombre = $datos['Nombre'];
            $usuario->correo = $datos['correo'];
            $usuario->telefono = $datos['telefono'];

            if (! empty($datos['contrasenha'])) {
                $usuario->contrasenha = Hash::make($datos['contrasenha']);
            }

            $usuario->save();

            if ($actualizarServicio) {
                $usuario->especialidad()->sync($servicioIds);
                $usuario->especialidad = $servicioIds ? implode(',', $servicioIds) : null;
                $usuario->save();
            }

            if ($horarios !== null) {
                $idsHorarios = collect($horarios)->map(function ($horario) {
                    return horario::firstOrCreate([
                        'diasemana' => $horario['diasemana'],
                        'horainicio' => $horario['horainicio'].':00',
                        'horafin' => $horario['horafin'].':00',
                    ])->idhorario;
                });

                $usuario->horariosLaborales()->sync($idsHorarios);
            }
        });

        $usuario->load('horariosLaborales');

        return response()->json([
            'message' => 'Perfil actualizado correctamente.',
            'data' => $this->datosPerfil($usuario),
        ]);
    }

    private function datosPerfil(usuario $usuario): array
    {
        $foto = $usuario->foto_perfil;
        $esBarbero = $usuario->roles?->Nombre_rol === 'Barbero';

        return [
            'idUsuario' => $usuario->idUsuario,
            'Nombre' => $usuario->Nombre,
            'correo' => $usuario->correo,
            'telefono' => $usuario->telefono,
            'servicio_ids' => $esBarbero
            ? $usuario->especialidad()->pluck('servicio.idServicio')->values()
            : [],
            'horarios' => $esBarbero
                ? $usuario->horariosLaborales->map(fn ($horario) => [
                    'diasemana' => $horario->diasemana,
                    'horainicio' => substr($horario->horainicio, 0, 5),
                    'horafin' => substr($horario->horafin, 0, 5),
                ])->values()
                : [],
            'foto_perfil' => $foto && ! str_starts_with($foto, 'http')
                ? Storage::disk('public')->url($foto)
                : $foto,
        ];
    }
}

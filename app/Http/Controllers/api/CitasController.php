<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Citas;
use App\Models\Servicio;
use App\Models\factura;
use App\Models\usuario;
use DateTimeImmutable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CitasController extends Controller
{
    public function disponibilidad(Request $request)
    {
        $datosValidados = $request->validate([
            'barbero_id' => 'required|integer|exists:usuario,idUsuario',
            'fecha' => 'required|date_format:Y-m-d|after_or_equal:today',
            'duracion' => 'required|integer|min:1',
        ]);

        $barbero = usuario::with('horariosLaborales')
            ->where('Roles_IDRol', 2)
            ->find($datosValidados['barbero_id']);

        if (!$barbero) {
            return response()->json(['message' => 'El barbero seleccionado no existe.'], 404);
        }

        $fecha = new DateTimeImmutable($datosValidados['fecha']);
        $horarios = $barbero->horariosLaborales->filter(fn($horario) => $this->horarioCorrespondeAlDia($horario->diasemana, $fecha));
        $intervalosOcupados = $this->obtenerIntervalosOcupados($barbero->idUsuario, $datosValidados['fecha']);
        $horasDisponibles = [];
        $ahora = new DateTimeImmutable();

        foreach ($horarios as $horario) {
            $inicioJornada = new DateTimeImmutable($fecha->format('Y-m-d') . ' ' . $horario->horainicio);
            $finJornada = new DateTimeImmutable($fecha->format('Y-m-d') . ' ' . $horario->horafin);

            for ($inicio = $inicioJornada; $inicio->modify('+30 minutes') <= $finJornada; $inicio = $inicio->modify('+30 minutes')) {
                $fin = $inicio->modify('+' . (int) $datosValidados['duracion'] . ' minutes');
                if ($fin > $finJornada || ($fecha->format('Y-m-d') === $ahora->format('Y-m-d') && $inicio < $ahora)) {
                    continue;
                }

                if ($this->intervaloDisponible($inicio, $fin, $intervalosOcupados)) {
                    $horasDisponibles[$inicio->format('H:i')] = $inicio->format('H:i');
                }
            }
        }

        return response()->json(['data' => array_values($horasDisponibles)]);
    }

    public function index()
    {
        $user = Auth::user();

        if (!$user || !$user->roles) {
            return response()->json(['result' => 'error', 'message' => 'Rol no definido'], 403);
        }


        $query = Citas::with([
            'cliente:idUsuario,Nombre,correo,telefono',
            'barbero:idUsuario,Nombre,correo,telefono,especialidad',
            'servicios',
            'factura'
        ]);

        if ($user->roles->Nombre_rol === 'Administrador') {
            return response()->json($query->get(), 200);
        }

        if ($user->roles->Nombre_rol === 'Cliente') {
            return response()->json($query->where('Usuario_idUsuarioCli', $user->idUsuario)->get(), 200);
        }

        return response()->json(['result' => 'error', 'message' => 'sin permisos'], 403);
    }

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'Fecha_hora' => 'required|date',
            'Usuario_idUsuarioBar' => 'required|exists:usuario,idUsuario',
            'Usuario_idUsuarioCli' => 'nullable|exists:usuario,idUsuario',
            'es_continuo' => 'required|boolean',
            'servicios' => 'required|array|min:1',
            'servicios.*.idServicio' => 'required|exists:servicio,idServicio',
            'servicios.*.fecha_hora_servicio' => 'required_if:es_continuo,false|nullable|date',
            'metodo_pago' => 'required|in:Efectivo,Nequi,Tarjeta,Transferencia,Pendiente_Pago',
        ], [
            'Fecha_hora.required' => 'El campo Fecha_hora es obligatorio.',
            'Fecha_hora.date' => 'El campo Fecha_hora debe ser una fecha válida.',
            'Usuario_idUsuarioBar.required' => 'El campo Usuario_idUsuarioBar es obligatorio.',
            'Usuario_idUsuarioBar.exists' => 'El barbero seleccionado no existe.',
            'Usuario_idUsuarioCli.exists' => 'El cliente seleccionado no existe.',
            'es_continuo.required' => 'El campo es_continuo es obligatorio.',
            'es_continuo.boolean' => 'El campo es_continuo debe ser verdadero o falso.',
            'servicios.required' => 'Debe seleccionar al menos un servicio.',
            'servicios.array' => 'El campo servicios debe ser un arreglo.',
            'servicios.min' => 'Debe seleccionar al menos un servicio.',
            'servicios.*.idServicio.required' => 'Cada servicio debe tener un idServicio válido.',
            'servicios.*.idServicio.exists' => 'Uno de los servicios seleccionados no existe.',
            'servicios.*.fecha_hora_servicio.required_if' => 'La fecha y hora del servicio es obligatoria cuando es_continuo es falso.',
            'servicios.*.fecha_hora_servicio.date' => 'La fecha y hora del servicio debe ser una fecha válida.',
            'metodo_pago.required' => 'El campo metodo_pago es obligatorio.',
            'metodo_pago.in' => 'El metodo_pago debe ser uno de los siguientes: Efectivo, Nequi, Tarjeta, Transferencia, Pendiente_Pago.'
        ]);

        if ($validator->fails()) {
            return response()->json(['result' => 'error', 'message' => 'Error de validación', 'errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        if (!$user || !$user->roles) {
            return response()->json(['result' => 'error', 'message' => 'Rol no definido'], 403);
        }

        $idCliente = $user->roles->Nombre_rol === 'Cliente'
            ? $user->idUsuario
            : $request->input('Usuario_idUsuarioCli', $user->idUsuario);

        try {
            DB::beginTransaction();

            $barbero = usuario::with('horariosLaborales')
                ->where('Roles_IDRol', 2)
                ->lockForUpdate()
                ->find($request->input('Usuario_idUsuarioBar'));

            $serviciosInput = $request->input('servicios');
            $serviciosSeleccionados = Servicio::whereIn(
                'idServicio',
                collect($serviciosInput)->pluck('idServicio')->unique()
            )->get()->keyBy('idServicio');
            if (!$barbero) {
                DB::rollBack();
                return response()->json(['message' => 'El barbero seleccionado no existe.'], 422);
            }

            $esContinuo = $request->boolean('es_continuo');
            $horaSiguiente = new DateTimeImmutable($request->input('Fecha_hora'));
            $intervalosPorFecha = [];
            $horariosServicios = [];

            foreach ($serviciosInput as $servicioInput) {
                $servicioSeleccionado = $serviciosSeleccionados->get($servicioInput['idServicio']);
                $inicioServicio = $esContinuo
                    ? $horaSiguiente
                    : new DateTimeImmutable($servicioInput['fecha_hora_servicio']);
                $finServicio = $inicioServicio->modify('+' . max(1, (int) $servicioSeleccionado->Duracion) . ' minutes');
                $fechaServicio = $inicioServicio->format('Y-m-d');

                if (!$this->estaDentroDelHorario($barbero, $inicioServicio, $finServicio)) {
                    DB::rollBack();
                    return response()->json(['message' => 'Uno de los servicios está fuera del horario laboral del barbero.'], 422);
                }

                if (!array_key_exists($fechaServicio, $intervalosPorFecha)) {
                    $intervalosPorFecha[$fechaServicio] = $this->obtenerIntervalosOcupados($barbero->idUsuario, $fechaServicio);
                }

                if (!$this->intervaloDisponible($inicioServicio, $finServicio, $intervalosPorFecha[$fechaServicio])) {
                    DB::rollBack();
                    return response()->json(['message' => 'Uno de los horarios ya no está disponible. Elige otro horario.'], 422);
                }

                $intervalosPorFecha[$fechaServicio][] = [$inicioServicio, $finServicio];
                $horariosServicios[$servicioInput['idServicio']] = $esContinuo
                    ? null
                    : $inicioServicio->format('Y-m-d H:i:s');
                if ($esContinuo) {
                    $horaSiguiente = $finServicio;
                }
            }


            $cita = Citas::create([
                'Fecha_hora' => $request->input('Fecha_hora'),
                'estado' => 'Confirmado',
                'Valora_Idvalora' => null,
                'Usuario_idUsuarioCli' => $idCliente,
                'Usuario_idUsuarioBar' => $request->input('Usuario_idUsuarioBar'),
            ]);


            $pivotData = [];
            $totalPagar = 0;

            foreach ($serviciosInput as $srv) {
                $servicioModel = $serviciosSeleccionados->get($srv['idServicio']);
                $totalPagar += $servicioModel->Precio;

                $pivotData[$srv['idServicio']] = [
                    'fecha_hora_servicio' => $horariosServicios[$srv['idServicio']]
                ];
            }

            $cita->servicios()->attach($pivotData);


            $factura = factura::create([
                'numero_factura' => 'TEMP',
                'fecha_emision' => now(),
                'subtotal' => $totalPagar,
                'total_pagar' => $totalPagar,
                'metodo_pago' => $request->input('metodo_pago', 'Pendiente_Pago'),
                'estado_factura' => 'Emitida',
                'Cita_idCita' => $cita->idCita,
                'Usuario_idUsuario' => $idCliente,
            ]);

            DB::commit();

            return response()->json($cita->load(['cliente', 'barbero', 'servicios', 'factura.cita.servicios']), 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['result' => 'error', 'message' => 'Error al crear la cita y factura', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'Fecha_hora' => 'sometimes|required|date',
            'estado' => 'sometimes|required|in:Confirmado,Pendiente,Cancelado',
            'Usuario_idUsuarioBar' => 'sometimes|required|exists:usuario,idUsuario',
            'servicios' => 'sometimes|required|array|min:1',
            'servicios.*.idServicio' => 'required_with:servicios|exists:servicio,idServicio',
        ], [
            'Fecha_hora.required' => 'El campo Fecha_hora es obligatorio.',
            'Fecha_hora.date' => 'El campo Fecha_hora debe ser una fecha válida.',
            'estado.required' => 'El campo estado es obligatorio.',
            'estado.in' => 'El estado debe ser uno de los siguientes: Confirmado, Pendiente, Cancelado.',
            'Usuario_idUsuarioBar.required' => 'El campo Usuario_idUsuarioBar es obligatorio.',
            'Usuario_idUsuarioBar.exists' => 'El barbero seleccionado no existe.',
            'servicios.required' => 'Debe seleccionar al menos un servicio.',
            'servicios.array' => 'El campo servicios debe ser un arreglo.',
            'servicios.min' => 'Debe seleccionar al menos un servicio.',
            'servicios.*.idServicio.required_with' => 'Cada servicio debe tener un idServicio válido.',
            'servicios.*.idServicio.exists' => 'Uno de los servicios seleccionados no existe.',
        ]);

        if ($validator->fails()) {
            return response()->json(['result' => 'error', 'message' => 'Error de validación', 'errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $cita = Citas::find($id);
        $cita->load(['cliente', 'cliente.roles', 'barbero', 'servicios', 'factura']);

        //return response()->json(['cita' => $cita], 200);

        if (!$cita) {
            return response()->json(['result' => 'error', 'message' => 'Cita no encontrada'], 404);
        }

        if ($user->roles->Nombre_rol === 'Cliente' && $cita->Usuario_idUsuarioCli !== $user->idUsuario) {
            return response()->json(['result' => 'error', 'message' => 'Sin autorización'], 403);
        }

        try {
            DB::beginTransaction();

            $dataToUpdate = $request->only(['Usuario_idUsuarioBar', 'estado']);

            //return response()->json(['dataToUpdate' => $dataToUpdate], 200);

            if ($request->has('Fecha_hora') && $request->input('Fecha_hora') !== $cita->Fecha_hora) {
                $dataToUpdate['Fecha_hora'] = $request->input('Fecha_hora');
                $dataToUpdate['estado'] = 'Pendiente';
            }

            if (isset($dataToUpdate['estado']) && $dataToUpdate['estado'] === 'Cancelado') {
                if ($cita->factura) {
                    $cita->factura()->update(['estado_factura' => 'Anulada']);
                    /*$cita->factura->estado_factura = 'Anulada';
                    $cita->factura->save();*/
                    //return response()->json(['message' => 'Cita y factura canceladas correctamente', 'Factura' => $cita->factura], 200);
                }
            }

            $cita->update($dataToUpdate);


            if ($request->has('servicios')) {
                $idsServicios = array_column($request->input('servicios'), 'idServicio');
                $cita->servicios()->sync($idsServicios);


                $nuevoTotal = Servicio::whereIn('idServicio', $idsServicios)->sum('Precio');


                if ($cita->factura) {
                    $cita->factura->update([
                        'subtotal' => $nuevoTotal,
                        'total_pagar' => $nuevoTotal
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Cita y factura editados correctamente',
                'cita' => $cita->load(['cliente', 'barbero', 'servicios', 'factura.cita.servicios'])
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['result' => 'error', 'message' => 'Error al actualizar cita', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        $cita = Citas::find($id);
        if ($cita) {
            $cita->estado = "Cancelado";
            $cita->save();
            return response()->json(['result' => 'ok', 'message' => 'cita eliminada']);
        } else {
            return response()->json(['result' => 'error', 'message' => 'cita no encontrada'], 404);
        }

    }

    private function horarioCorrespondeAlDia(string $diaHorario, DateTimeImmutable $fecha): bool
    {
        $dias = [
            1 => ['lunes', 'monday'],
            2 => ['martes', 'tuesday'],
            3 => ['miercoles', 'wednesday'],
            4 => ['jueves', 'thursday'],
            5 => ['viernes', 'friday'],
            6 => ['sabado', 'saturday'],
            7 => ['domingo', 'sunday'],
        ];
        $diaNormalizado = Str::lower(Str::ascii(trim($diaHorario)));

        return in_array($diaNormalizado, $dias[(int) $fecha->format('N')], true);
    }

    private function estaDentroDelHorario(usuario $barbero, DateTimeImmutable $inicio, DateTimeImmutable $fin): bool
    {
        foreach ($barbero->horariosLaborales as $horario) {
            if (!$this->horarioCorrespondeAlDia($horario->diasemana, $inicio)) {
                continue;
            }

            $inicioJornada = new DateTimeImmutable($inicio->format('Y-m-d') . ' ' . $horario->horainicio);
            $finJornada = new DateTimeImmutable($inicio->format('Y-m-d') . ' ' . $horario->horafin);
            if ($inicio >= $inicioJornada && $fin <= $finJornada) {
                return true;
            }
        }

        return false;
    }

    private function obtenerIntervalosOcupados(int $barberoId, string $fecha): array
    {
        $citas = Citas::with('servicios')
            ->where('Usuario_idUsuarioBar', $barberoId)
            ->whereDate('Fecha_hora', $fecha)
            ->where('estado', '!=', 'Cancelado')
            ->get();

        $intervalos = [];
        foreach ($citas as $cita) {
            $serviciosConHora = $cita->servicios->filter(fn($servicio) => $servicio->pivot->fecha_hora_servicio);
            if ($serviciosConHora->isNotEmpty()) {
                foreach ($serviciosConHora as $servicio) {
                    $inicio = new DateTimeImmutable($servicio->pivot->fecha_hora_servicio);
                    $fin = $inicio->modify('+' . max(1, (int) $servicio->Duracion) . ' minutes');
                    $intervalos[] = [$inicio, $fin];
                }
                continue;
            }

            $inicio = new DateTimeImmutable($cita->Fecha_hora);
            $duracion = (int) $cita->servicios->sum('Duracion');
            $fin = $inicio->modify('+' . max(1, $duracion ?: 60) . ' minutes');
            $intervalos[] = [$inicio, $fin];
        }

        return $intervalos;
    }

    private function intervaloDisponible(DateTimeImmutable $inicio, DateTimeImmutable $fin, array $intervalosOcupados): bool
    {
        foreach ($intervalosOcupados as [$inicioOcupado, $finOcupado]) {
            if ($inicio < $finOcupado && $fin > $inicioOcupado) {
                return false;
            }
        }

        return true;
    }
}

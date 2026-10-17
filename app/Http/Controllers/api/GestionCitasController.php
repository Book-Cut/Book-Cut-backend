<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Citas;
use App\Models\factura;
use App\Models\Servicio;
use App\Models\usuario;
use DateTimeImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class GestionCitasController extends Controller
{
    public function disponibilidad(Request $request)
    {
        $datosValidados = $request->validate([
            'barbero_id' => 'required|integer|exists:usuario,idUsuario',
            'fecha' => 'required|date_format:Y-m-d|after_or_equal:today',
            'duracion' => 'required|integer|min:1',
            'cita_id' => 'sometimes|integer|exists:cita,idCita',
        ]);

        $barbero = usuario::with('horariosLaborales')
            ->where('Roles_IDRol', 2)
            ->find($datosValidados['barbero_id']);

        if (! $barbero) {
            return response()->json(['message' => 'El barbero seleccionado no existe.'], 404);
        }

        $citaExcluir = null;
        if (isset($datosValidados['cita_id'])) {
            $citaExcluir = Citas::find($datosValidados['cita_id']);
            $usuario = $request->user();

            if (
                ! $usuario
                || ! $citaExcluir
                || $usuario->roles?->Nombre_rol !== 'Cliente'
                || (int) $citaExcluir->Usuario_idUsuarioCli !== (int) $usuario->idUsuario
                || (int) $citaExcluir->Usuario_idUsuarioBar !== (int) $barbero->idUsuario
                || in_array($citaExcluir->estado, ['Cancelado', 'Finalizado'], true)
                || new DateTimeImmutable($citaExcluir->Fecha_hora) <= new DateTimeImmutable
            ) {
                return response()->json(['message' => 'No tienes permiso para consultar esta cita.'], 403);
            }
        }

        $fecha = new DateTimeImmutable($datosValidados['fecha']);
        $horarios = $barbero->horariosLaborales->filter(fn ($horario) => $this->horarioCorrespondeAlDia($horario->diasemana, $fecha));
        $intervalosOcupados = $this->obtenerIntervalosOcupados(
            $barbero->idUsuario,
            $datosValidados['fecha'],
            $citaExcluir?->idCita,
        );
        $horasDisponibles = [];
        $ahora = new DateTimeImmutable;

        foreach ($horarios as $horario) {
            $inicioJornada = new DateTimeImmutable($fecha->format('Y-m-d').' '.$horario->horainicio);
            $finJornada = new DateTimeImmutable($fecha->format('Y-m-d').' '.$horario->horafin);

            for ($inicio = $inicioJornada; $inicio->modify('+30 minutes') <= $finJornada; $inicio = $inicio->modify('+30 minutes')) {
                $fin = $inicio->modify('+'.(int) $datosValidados['duracion'].' minutes');
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

    public function reagendar(Request $request, int $id)
    {
        $datos = $request->validate([
            'fecha' => 'required|date_format:Y-m-d|after_or_equal:today',
            'hora' => 'required|date_format:H:i',
        ]);

        $usuario = $request->user();
        $cita = Citas::find($id);

        if (! $cita) {
            return response()->json(['message' => 'Cita no encontrada.'], 404);
        }

        if (
            ! $usuario
            || $usuario->roles?->Nombre_rol !== 'Cliente'
            || (int) $cita->Usuario_idUsuarioCli !== (int) $usuario->idUsuario
        ) {
            return response()->json(['message' => 'No tienes permiso para reagendar esta cita.'], 403);
        }

        if (in_array($cita->estado, ['Cancelado', 'Finalizado'], true)) {
            return response()->json(['message' => 'Esta cita ya no se puede reagendar.'], 422);
        }
        if (! $this->estaDentroDelPlazoMinimo($cita)) {
            return response()->json(['message' => 'Solo puedes modificar la cita hasta 2 horas antes de su inicio.'], 422);
        }

        return DB::transaction(function () use ($datos, $id, $usuario, $cita) {
            $barbero = usuario::with('horariosLaborales')
                ->where('Roles_IDRol', 2)
                ->lockForUpdate()
                ->find($cita->Usuario_idUsuarioBar);

            $cita = Citas::with('servicios')->lockForUpdate()->find($id);

            if (
                ! $cita
                || (int) $cita->Usuario_idUsuarioCli !== (int) $usuario->idUsuario
                || in_array($cita->estado, ['Cancelado', 'Finalizado'], true)
                || ! $this->estaDentroDelPlazoMinimo($cita)
            ) {
                return response()->json(['message' => 'Esta cita ya no se puede reagendar. Debes hacerlo al menos 2 horas antes.'], 422);
            }

            if (! $barbero || $cita->servicios->isEmpty()) {
                return response()->json(['message' => 'No se pudo validar el barbero o los servicios de la cita.'], 422);
            }

            $inicioCita = new DateTimeImmutable($datos['fecha'].' '.$datos['hora']);
            if ($inicioCita <= new DateTimeImmutable) {
                return response()->json(['message' => 'Selecciona una fecha y hora futuras.'], 422);
            }

            $fecha = $inicioCita->format('Y-m-d');
            $intervalosOcupados = $this->obtenerIntervalosOcupados(
                $barbero->idUsuario,
                $fecha,
                $cita->idCita,
            );
            $horariosServicios = [];
            $horaSiguiente = $inicioCita;

            foreach ($cita->servicios as $servicio) {
                $finServicio = $horaSiguiente->modify('+'.max(1, (int) $servicio->Duracion).' minutes');

                if (! $this->estaDentroDelHorario($barbero, $horaSiguiente, $finServicio)) {
                    return response()->json(['message' => 'La cita queda fuera del horario laboral del barbero.'], 422);
                }

                if (! $this->intervaloDisponible($horaSiguiente, $finServicio, $intervalosOcupados)) {
                    return response()->json(['message' => 'Ese horario ya está ocupado. Elige otro horario.'], 422);
                }

                $intervalosOcupados[] = [$horaSiguiente, $finServicio];
                $horariosServicios[$servicio->idServicio] = [
                    'fecha_hora_servicio' => $horaSiguiente->format('Y-m-d H:i:s'),
                ];
                $horaSiguiente = $finServicio;
            }

            $cita->Fecha_hora = $inicioCita->format('Y-m-d H:i:s');
            $cita->estado = 'Pendiente';
            $cita->save();
            $cita->servicios()->sync($horariosServicios);

            return response()->json([
                'message' => 'Cita reagendada correctamente.',
                'cita' => $cita->load(['cliente', 'barbero', 'servicios', 'factura']),
            ]);
        });
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
            'metodo_pago.in' => 'El metodo_pago debe ser uno de los siguientes: Efectivo, Nequi, Tarjeta, Transferencia, Pendiente_Pago.',
        ]);

        if ($validator->fails()) {
            return response()->json(['result' => 'error', 'message' => 'Error de validación', 'errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        if (! $user || ! $user->roles) {
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
            if (! $barbero) {
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
                $finServicio = $inicioServicio->modify('+'.max(1, (int) $servicioSeleccionado->Duracion).' minutes');
                $fechaServicio = $inicioServicio->format('Y-m-d');

                if (! $this->estaDentroDelHorario($barbero, $inicioServicio, $finServicio)) {
                    DB::rollBack();

                    return response()->json(['message' => 'Uno de los servicios está fuera del horario laboral del barbero.'], 422);
                }

                if (! array_key_exists($fechaServicio, $intervalosPorFecha)) {
                    $intervalosPorFecha[$fechaServicio] = $this->obtenerIntervalosOcupados($barbero->idUsuario, $fechaServicio);
                }

                if (! $this->intervaloDisponible($inicioServicio, $finServicio, $intervalosPorFecha[$fechaServicio])) {
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
                    'fecha_hora_servicio' => $horariosServicios[$srv['idServicio']],
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
            'servicios.*.idServicio' => 'required_with:servicios|distinct|exists:servicio,idServicio',
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
        if (! $cita) {
            return response()->json(['result' => 'error', 'message' => 'Cita no encontrada'], 404);
        }

        if (! $user || ! $user->roles) {
            return response()->json(['result' => 'error', 'message' => 'Rol no definido'], 403);
        }

        $esCliente = $user->roles->Nombre_rol === 'Cliente';
        if ($esCliente && (int) $cita->Usuario_idUsuarioCli !== (int) $user->idUsuario) {
            return response()->json(['result' => 'error', 'message' => 'Sin autorización'], 403);
        }

        $modificaProgramacion = $request->hasAny(['Fecha_hora', 'Usuario_idUsuarioBar', 'servicios']);
        $cancelaCita = $request->input('estado') === 'Cancelado';
        if (
            $esCliente
            && ($modificaProgramacion || $cancelaCita)
            && (
                in_array($cita->estado, ['Cancelado', 'Finalizado'], true)
                || ! $this->estaDentroDelPlazoMinimo($cita)
            )
        ) {
            return response()->json(['result' => 'error', 'message' => 'Solo puedes modificar o cancelar la cita hasta 2 horas antes de su inicio.'], 422);
        }

        try {
            DB::beginTransaction();

            $cita = Citas::with(['servicios', 'factura'])->lockForUpdate()->find($id);
            if (! $cita) {
                DB::rollBack();

                return response()->json(['result' => 'error', 'message' => 'Cita no encontrada'], 404);
            }

            if (
                $esCliente
                && (
                    (int) $cita->Usuario_idUsuarioCli !== (int) $user->idUsuario
                    || in_array($cita->estado, ['Cancelado', 'Finalizado'], true)
                    || (($modificaProgramacion || $cancelaCita) && ! $this->estaDentroDelPlazoMinimo($cita))
                )
            ) {
                DB::rollBack();

                return response()->json(['result' => 'error', 'message' => 'No puedes modificar o cancelar esta cita. Debes hacerlo al menos 2 horas antes.'], 422);
            }

            $dataToUpdate = $request->only(['Usuario_idUsuarioBar', 'estado']);
            $fechaAnterior = $cita->Fecha_hora;
            if ($request->has('Fecha_hora') && $request->input('Fecha_hora') !== $fechaAnterior) {
                $dataToUpdate['Fecha_hora'] = $request->input('Fecha_hora');
                $dataToUpdate['estado'] = 'Pendiente';
            }

            $horariosServicios = null;
            if ($modificaProgramacion) {
                $inicioCita = new DateTimeImmutable($request->input('Fecha_hora', $cita->Fecha_hora));
                if ($esCliente && $inicioCita <= new DateTimeImmutable) {
                    DB::rollBack();

                    return response()->json(['result' => 'error', 'message' => 'La nueva fecha y hora deben ser futuras.'], 422);
                }

                $barberoId = (int) $request->input('Usuario_idUsuarioBar', $cita->Usuario_idUsuarioBar);
                $barbero = usuario::with('horariosLaborales')
                    ->where('Roles_IDRol', 2)
                    ->lockForUpdate()
                    ->find($barberoId);

                if (! $barbero) {
                    DB::rollBack();

                    return response()->json(['result' => 'error', 'message' => 'El barbero seleccionado no existe.'], 422);
                }

                if ($request->has('servicios')) {
                    $idsServicios = array_column($request->input('servicios'), 'idServicio');
                    $catalogoServicios = Servicio::whereIn('idServicio', $idsServicios)->get()->keyBy('idServicio');
                    $servicios = collect($idsServicios)->map(fn ($idServicio) => $catalogoServicios->get($idServicio));
                } else {
                    $servicios = $cita->servicios;
                    $idsServicios = $servicios->pluck('idServicio')->all();
                }

                if ($servicios->isEmpty()) {
                    DB::rollBack();

                    return response()->json(['result' => 'error', 'message' => 'La cita debe tener al menos un servicio.'], 422);
                }

                $horaSiguiente = $inicioCita;
                $intervalosPorFecha = [];
                $horariosServicios = [];
                foreach ($servicios as $servicio) {
                    $finServicio = $horaSiguiente->modify('+'.max(1, (int) $servicio->Duracion).' minutes');
                    if (! $this->estaDentroDelHorario($barbero, $horaSiguiente, $finServicio)) {
                        DB::rollBack();

                        return response()->json(['result' => 'error', 'message' => 'La cita queda fuera del horario laboral del barbero.'], 422);
                    }

                    $fechaServicio = $horaSiguiente->format('Y-m-d');
                    if (! array_key_exists($fechaServicio, $intervalosPorFecha)) {
                        $intervalosPorFecha[$fechaServicio] = $this->obtenerIntervalosOcupados(
                            $barbero->idUsuario,
                            $fechaServicio,
                            (int) $cita->idCita,
                        );
                    }

                    if (! $this->intervaloDisponible($horaSiguiente, $finServicio, $intervalosPorFecha[$fechaServicio])) {
                        DB::rollBack();

                        return response()->json(['result' => 'error', 'message' => 'Ese horario ya está ocupado. Elige otro horario.'], 422);
                    }

                    $intervalosPorFecha[$fechaServicio][] = [$horaSiguiente, $finServicio];
                    $horariosServicios[$servicio->idServicio] = [
                        'fecha_hora_servicio' => $horaSiguiente->format('Y-m-d H:i:s'),
                    ];
                    $horaSiguiente = $finServicio;
                }
            }

            $cita->update($dataToUpdate);

            if ($request->has('servicios')) {
                $nuevoTotal = Servicio::whereIn('idServicio', $idsServicios)->sum('Precio');
                $cita->servicios()->sync($horariosServicios);
                if ($cita->factura) {
                    $cita->factura->update(['subtotal' => $nuevoTotal, 'total_pagar' => $nuevoTotal]);
                }
            } elseif ($horariosServicios !== null) {
                $cita->servicios()->sync($horariosServicios);
            }

            if (isset($dataToUpdate['estado']) && $dataToUpdate['estado'] === 'Cancelado' && $cita->factura) {
                $cita->factura()->update(['estado_factura' => 'Anulada']);
            }

            DB::commit();

            return response()->json([
                'message' => 'Cita y factura editados correctamente',
                'cita' => $cita->load(['cliente', 'barbero', 'servicios', 'factura.cita.servicios']),
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['result' => 'error', 'message' => 'Error al actualizar cita', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        $cita = Citas::find($id);
        if (! $cita) {
            return response()->json(['result' => 'error', 'message' => 'cita no encontrada'], 404);
        }

        $user = $request->user();
        if (! $user || ! $user->roles) {
            return response()->json(['result' => 'error', 'message' => 'Rol no definido'], 403);
        }

        if ($user->roles->Nombre_rol === 'Cliente') {
            if ((int) $cita->Usuario_idUsuarioCli !== (int) $user->idUsuario) {
                return response()->json(['result' => 'error', 'message' => 'Sin autorización'], 403);
            }
            if (
                in_array($cita->estado, ['Cancelado', 'Finalizado'], true)
                || ! $this->estaDentroDelPlazoMinimo($cita)
            ) {
                return response()->json(['result' => 'error', 'message' => 'Solo puedes cancelar la cita hasta 2 horas antes de su inicio.'], 422);
            }
        }

        $cita->estado = 'Cancelado';
        $cita->save();
        $cita->factura()->update(['estado_factura' => 'Anulada']);

        return response()->json(['result' => 'ok', 'message' => 'cita eliminada']);
    }

    private function estaDentroDelPlazoMinimo(Citas $cita): bool
    {
        return new DateTimeImmutable($cita->Fecha_hora) >= now()->addHours(2)->toDateTimeImmutable();
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
            if (! $this->horarioCorrespondeAlDia($horario->diasemana, $inicio)) {
                continue;
            }

            $inicioJornada = new DateTimeImmutable($inicio->format('Y-m-d').' '.$horario->horainicio);
            $finJornada = new DateTimeImmutable($inicio->format('Y-m-d').' '.$horario->horafin);
            if ($inicio >= $inicioJornada && $fin <= $finJornada) {
                return true;
            }
        }

        return false;
    }

    private function obtenerIntervalosOcupados(int $barberoId, string $fecha, ?int $citaExcluir = null): array
    {
        $citas = Citas::with('servicios')
            ->where('Usuario_idUsuarioBar', $barberoId)
            ->whereDate('Fecha_hora', $fecha)
            ->where('estado', '!=', 'Cancelado')
            ->when($citaExcluir, fn ($query) => $query->where('idCita', '!=', $citaExcluir))
            ->get();

        $intervalos = [];
        foreach ($citas as $cita) {
            $serviciosConHora = $cita->servicios->filter(fn ($servicio) => $servicio->pivot->fecha_hora_servicio);
            if ($serviciosConHora->isNotEmpty()) {
                foreach ($serviciosConHora as $servicio) {
                    $inicio = new DateTimeImmutable($servicio->pivot->fecha_hora_servicio);
                    $fin = $inicio->modify('+'.max(1, (int) $servicio->Duracion).' minutes');
                    $intervalos[] = [$inicio, $fin];
                }

                continue;
            }

            $inicio = new DateTimeImmutable($cita->Fecha_hora);
            $duracion = (int) $cita->servicios->sum('Duracion');
            $fin = $inicio->modify('+'.max(1, $duracion ?: 60).' minutes');
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

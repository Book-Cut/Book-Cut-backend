<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Citas;
use App\Models\factura as Factura;
use App\Models\Servicio;
use App\Models\usuario;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Collection;
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
            $citaExcluir = Citas::with('servicios')->find($datosValidados['cita_id']);
            $usuario = $request->user();

            if (
                ! $usuario
                || ! $citaExcluir
                || $usuario->roles?->Nombre_rol !== 'Cliente'
                || (int) $citaExcluir->Usuario_idUsuarioCli !== (int) $usuario->idUsuario
                || (int) $citaExcluir->Usuario_idUsuarioBar !== (int) $barbero->idUsuario
            ) {
                return response()->json(['message' => 'No tienes permiso para consultar esta cita.'], 403);
            }

            if (
                in_array($citaExcluir->estado, ['Cancelado', 'Finalizado'], true)
                || ! $this->estaDentroDelPlazoMinimo($citaExcluir)
                || $this->inicioCita($citaExcluir) <= new DateTimeImmutable
            ) {
                return response()->json(['message' => 'Esta cita ya no permite consultar horarios para reagendar.'], 422);
            }
        }

        $fecha = new DateTimeImmutable($datosValidados['fecha']);
        $horarios = $barbero->horariosLaborales->filter(fn ($horario) => $this->horarioCorrespondeAlDia($horario->diasemana, $fecha));
        $intervalosOcupados = $this->obtenerIntervalosOcupados(
            $barbero->idUsuario,
            $datosValidados['fecha'],
            $citaExcluir?->idCita,
        );
        $serviciosReagendados = [];
        if ($citaExcluir) {
            $inicioOriginal = $this->inicioCita($citaExcluir);
            foreach ($this->serviciosProgramados($citaExcluir) as $programado) {
                $serviciosReagendados[] = [
                    'offset' => (int) (($programado['inicio']->getTimestamp() - $inicioOriginal->getTimestamp()) / 60),
                    'duracion' => max(1, $this->duracionServicio($programado['servicio'])),
                ];
            }
        }
        $horasDisponibles = [];
        $ahora = new DateTimeImmutable;

        foreach ($horarios as $horario) {
            $inicioJornada = new DateTimeImmutable($fecha->format('Y-m-d').' '.$horario->horainicio);
            $finJornada = new DateTimeImmutable($fecha->format('Y-m-d').' '.$horario->horafin);

            for ($inicio = $inicioJornada; $inicio->modify('+30 minutes') <= $finJornada; $inicio = $inicio->modify('+30 minutes')) {
                if ($fecha->format('Y-m-d') === $ahora->format('Y-m-d') && $inicio < $ahora) {
                    continue;
                }

                $segmentos = $serviciosReagendados ?: [[
                    'offset' => 0,
                    'duracion' => (int) $datosValidados['duracion'],
                ]];
                $intervalosTemporales = $intervalosOcupados;
                $disponible = true;
                foreach ($segmentos as $segmento) {
                    $inicioServicio = $inicio->modify('+'.$segmento['offset'].' minutes');
                    $finServicio = $inicioServicio->modify('+'.$segmento['duracion'].' minutes');
                    if (
                        ! $this->estaDentroDelHorario($barbero, $inicioServicio, $finServicio)
                        || ! $this->intervaloDisponible($inicioServicio, $finServicio, $intervalosTemporales)
                    ) {
                        $disponible = false;
                        break;
                    }
                    $intervalosTemporales[] = [$inicioServicio, $finServicio];
                }

                if ($disponible) {
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
            return response()->json(['message' => 'Solo puedes reagendar hasta dos horas antes de que comience la cita.'], 422);
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
                return response()->json(['message' => 'Esta cita ya no se puede reagendar. Debes hacerlo al menos dos horas antes.'], 422);
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
            $intervalosPorFecha = [$fecha => $intervalosOcupados];
            $inicioOriginal = $this->inicioCita($cita);
            $horariosServicios = [];
            foreach ($this->serviciosProgramados($cita) as $programado) {
                $servicio = $programado['servicio'];
                $desplazamiento = (int) (($programado['inicio']->getTimestamp() - $inicioOriginal->getTimestamp()) / 60);
                $inicioServicio = $inicioCita->modify('+'.$desplazamiento.' minutes');
                $finServicio = $inicioServicio->modify('+'.max(1, $this->duracionServicio($servicio)).' minutes');

                if (! $this->estaDentroDelHorario($barbero, $inicioServicio, $finServicio)) {
                    return response()->json(['message' => 'La cita queda fuera del horario laboral del barbero.'], 422);
                }

                $fechaServicio = $inicioServicio->format('Y-m-d');
                if (! isset($intervalosPorFecha[$fechaServicio])) {
                    $intervalosPorFecha[$fechaServicio] = $fechaServicio === $fecha
                        ? $intervalosOcupados
                        : $this->obtenerIntervalosOcupados($barbero->idUsuario, $fechaServicio, $cita->idCita);
                }
                if (! $this->intervaloDisponible($inicioServicio, $finServicio, $intervalosPorFecha[$fechaServicio])) {
                    return response()->json(['message' => 'Ese horario ya está ocupado. Elige otro horario.'], 422);
                }

                $intervalosPorFecha[$fechaServicio][] = [$inicioServicio, $finServicio];
                $horariosServicios[] = [
                    'idServicio' => $servicio->idServicio,
                    'fecha_hora_servicio' => $inicioServicio->format('Y-m-d H:i:s'),
                ] + $this->datosHistoricosServicio($servicio);
            }

            $cita->Fecha_hora = $inicioCita->format('Y-m-d H:i:s');
            $cita->fecha_agendada = now();
            $cita->estado = 'Confirmado';
            $cita->confirmacion_solicitada_at = null;
            $cita->save();
            DB::table('notificacion')
                ->where('Cita_idCita', $cita->idCita)
                ->where('evento', 'confirmacion')
                ->whereNotNull('token_confirmacion')
                ->update(['token_confirmacion' => null]);
            $cita->servicios()->detach();
            foreach ($horariosServicios as $horarioServicio) {
                $idServicio = $horarioServicio['idServicio'];
                unset($horarioServicio['idServicio']);
                $cita->servicios()->attach($idServicio, $horarioServicio);
            }
            $cita->refresh();

            return response()->json([
                'message' => 'Cita reagendada correctamente.',
                'cita' => $cita->load(['cliente', 'barbero', 'servicios', 'factura', 'facturas']),
            ]);
        });
    }

    public function confirmarAsistencia(Request $request, int $id)
    {
        $usuario = $request->user();

        return DB::transaction(function () use ($usuario, $id) {
            $cita = Citas::with('factura')->lockForUpdate()->find($id);

            if (! $cita) {
                return response()->json(['message' => 'Cita no encontrada.'], 404);
            }

            if (
                ! $usuario
                || $usuario->roles?->Nombre_rol !== 'Cliente'
                || (int) $cita->Usuario_idUsuarioCli !== (int) $usuario->idUsuario
            ) {
                return response()->json(['message' => 'No tienes permiso para confirmar esta cita.'], 403);
            }

            if ($cita->estado !== 'Pendiente' || ! $cita->confirmacion_solicitada_at) {
                return response()->json(['message' => 'Esta cita no está esperando confirmación.'], 422);
            }

            if (
                now()->greaterThanOrEqualTo($cita->confirmacion_solicitada_at->copy()->addMinutes(30))
                || $cita->Fecha_hora <= now()->toDateTimeString()
            ) {
                return response()->json([
                    'message' => 'El plazo para confirmar venció. La cita se cancelará automáticamente.',
                ], 422);
            }

            $cita->estado = 'Confirmado';
            $cita->confirmacion_solicitada_at = null;
            $cita->save();
            DB::table('notificacion')
                ->where('Cita_idCita', $cita->idCita)
                ->where('evento', 'confirmacion')
                ->where('fecha_hora_cita', $cita->Fecha_hora)
                ->where('estado_envio', 'Enviado')
                ->update([
                    'token_confirmacion' => null,
                    'leido_at' => now(),
                ]);

            return response()->json(['message' => 'Asistencia confirmada. La cita sigue confirmada.']);
        });
    }

    public function finalizar(Request $request, int $id)
    {
        $usuario = $request->user();
        if (! $usuario || ! $usuario->roles) {
            return response()->json(['message' => 'Rol no definido.'], 403);
        }

        return DB::transaction(function () use ($usuario, $id) {
            $cita = Citas::with(['cliente', 'barbero', 'servicios', 'factura'])
                ->lockForUpdate()
                ->find($id);

            if (! $cita) {
                return response()->json(['message' => 'Cita no encontrada.'], 404);
            }

            $esAdministrador = $usuario->roles->Nombre_rol === 'Administrador';
            $esBarberoAsignado = $usuario->roles->Nombre_rol === 'Barbero'
                && (int) $cita->Usuario_idUsuarioBar === (int) $usuario->idUsuario;

            if (! $esAdministrador && ! $esBarberoAsignado) {
                return response()->json(['message' => 'No tienes permiso para finalizar esta cita.'], 403);
            }

            if ($cita->estado !== 'Confirmado') {
                return response()->json(['message' => 'Solo se pueden finalizar citas confirmadas.'], 422);
            }

            $cita->estado = 'Finalizado';
            $cita->save();
            $cita->refresh();

            return response()->json([
                'message' => 'Cita finalizada correctamente. Registra el pago para generar la factura.',
                'cita' => $cita->load(['cliente', 'barbero', 'servicios', 'factura', 'facturas']),
            ]);
        });
    }

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'Fecha_hora' => 'required|date',
            'Usuario_idUsuarioBar' => 'required|exists:usuario,idUsuario',
            'Usuario_idUsuarioCli' => 'nullable|exists:usuario,idUsuario',
            'nombre_cliente' => 'required|string|max:255',
            'telefono_cliente' => 'required|string|max:45',
            'es_continuo' => 'required|boolean',
            'modalidad_pago' => 'sometimes|in:Presencial',
            'metodo_pago' => 'prohibited',
            'servicios' => 'required|array|min:1',
            'servicios.*.idServicio' => 'required|exists:servicio,idServicio',
            'servicios.*.fecha_hora_servicio' => 'required_if:es_continuo,false|nullable|date',
        ], [
            'Fecha_hora.required' => 'El campo Fecha_hora es obligatorio.',
            'Fecha_hora.date' => 'El campo Fecha_hora debe ser una fecha válida.',
            'Usuario_idUsuarioBar.required' => 'El campo Usuario_idUsuarioBar es obligatorio.',
            'Usuario_idUsuarioBar.exists' => 'El barbero seleccionado no existe.',
            'Usuario_idUsuarioCli.exists' => 'El cliente seleccionado no existe.',
            'nombre_cliente.required' => 'El nombre del cliente es obligatorio.',
            'nombre_cliente.string' => 'El nombre del cliente debe ser texto.',
            'nombre_cliente.max' => 'El nombre del cliente no puede superar 255 caracteres.',
            'telefono_cliente.required' => 'El teléfono del cliente es obligatorio.',
            'telefono_cliente.string' => 'El teléfono del cliente debe ser texto.',
            'telefono_cliente.max' => 'El teléfono del cliente no puede superar 45 caracteres.',
            'es_continuo.required' => 'El campo es_continuo es obligatorio.',
            'es_continuo.boolean' => 'El campo es_continuo debe ser verdadero o falso.',
            'servicios.required' => 'Debe seleccionar al menos un servicio.',
            'servicios.array' => 'El campo servicios debe ser un arreglo.',
            'servicios.min' => 'Debe seleccionar al menos un servicio.',
            'servicios.*.idServicio.required' => 'Cada servicio debe tener un idServicio válido.',
            'servicios.*.idServicio.exists' => 'Uno de los servicios seleccionados no existe.',
            'servicios.*.fecha_hora_servicio.required_if' => 'La fecha y hora del servicio es obligatoria cuando es_continuo es falso.',
            'servicios.*.fecha_hora_servicio.date' => 'La fecha y hora del servicio debe ser una fecha válida.',
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
            $serviciosAgendados = [];

            if (! $esContinuo) {
                usort($serviciosInput, fn ($a, $b) => strcmp(
                    $a['fecha_hora_servicio'],
                    $b['fecha_hora_servicio']
                ));
            }
            foreach ($serviciosInput as $servicioInput) {
                $servicioSeleccionado = $serviciosSeleccionados->get($servicioInput['idServicio']);
                $inicioServicio = $esContinuo
                    ? $horaSiguiente
                    : new DateTimeImmutable($servicioInput['fecha_hora_servicio']);
                $finServicio = $inicioServicio->modify('+'.max(1, $this->duracionServicio($servicioSeleccionado)).' minutes');
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
                $serviciosAgendados[] = [
                    'idServicio' => $servicioInput['idServicio'],
                    'fecha_hora_servicio' => $inicioServicio->format('Y-m-d H:i:s'),
                    'inicio' => $inicioServicio,
                    'fin' => $finServicio,
                ] + $this->datosHistoricosServicio($servicioSeleccionado);
                if ($esContinuo) {
                    $horaSiguiente = $finServicio;
                }
            }

            $inicioCita = collect($serviciosAgendados)
                ->sortBy(fn ($servicio) => $servicio['inicio']->getTimestamp())
                ->first()['inicio'];
            $cita = Citas::create([
                    'Fecha_hora' => $inicioCita->format('Y-m-d H:i:s'),
                    'fecha_agendada' => now(),
                    'estado' => 'Confirmado',
                    'estado_pago' => 'Pendiente',
                    'modalidad_pago' => 'Presencial',
                    'metodo_pago' => null,
                    'Valora_Idvalora' => null,
                    'Usuario_idUsuarioCli' => $idCliente,
                    'Usuario_idUsuarioBar' => $request->input('Usuario_idUsuarioBar'),
                    'nombre_cliente' => $request->input('nombre_cliente'),
                    'telefono_cliente' => $request->input('telefono_cliente'),
                ]);

            foreach ($serviciosAgendados as $servicioAgendado) {
                $cita->servicios()->attach($servicioAgendado['idServicio'], [
                    'fecha_hora_servicio' => $servicioAgendado['fecha_hora_servicio'],
                    'nombre_servicio' => $servicioAgendado['nombre_servicio'],
                    'duracion_servicio' => $servicioAgendado['duracion_servicio'],
                    'precio_servicio' => $servicioAgendado['precio_servicio'],
                ]);
            }
            $citasCreadas = [$cita->load(['cliente', 'barbero', 'servicios', 'factura.cita.servicios', 'facturas'])];

            DB::commit();

            return response()->json(['citas' => $citasCreadas], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['result' => 'error', 'message' => 'Error al crear la cita y procesar el pago', 'error' => $e->getMessage()], 500);
        }
    }

    public function registrarPago(Request $request, int $id)
    {
        $datos = $request->validate([
            'metodo_pago' => 'required|in:Efectivo,Nequi,Tarjeta,Transferencia',
        ]);

        return DB::transaction(function () use ($request, $id, $datos) {
            $usuario = $request->user();
            $cita = Citas::with('servicios')->lockForUpdate()->find($id);

            if (! $cita) {
                return response()->json(['message' => 'Cita no encontrada.'], 404);
            }

            $esAdministrador = $usuario?->roles?->Nombre_rol === 'Administrador';
            $esBarberoAsignado = $usuario?->roles?->Nombre_rol === 'Barbero'
                && (int) $cita->Usuario_idUsuarioBar === (int) $usuario->idUsuario;

            if (! $esAdministrador && ! $esBarberoAsignado) {
                return response()->json(['message' => 'No tienes permiso para registrar este pago.'], 403);
            }

            if ($cita->estado_pago !== 'Pendiente' || $cita->estado !== 'Finalizado') {
                return response()->json(['message' => 'Solo se puede registrar el pago presencial de una cita finalizada y pendiente de pago.'], 422);
            }

            $citasGrupo = $this->citasConsecutivas($cita);
            $cita->refresh();
            if ($cita->estado_pago !== 'Pendiente' || $cita->estado !== 'Finalizado') {
                return response()->json(['message' => 'El pago de esta cita ya fue registrado.'], 409);
            }
            if ($citasGrupo->contains(fn ($citaGrupo) => $citaGrupo->estado !== 'Finalizado')) {
                return response()->json(['message' => 'Finaliza todas las citas consecutivas antes de registrar el pago.'], 422);
            }
            if ($citasGrupo->contains(fn ($citaGrupo) => $citaGrupo->factura()->exists() || $citaGrupo->facturas()->exists())) {
                return response()->json(['message' => 'Una de las citas ya tiene una factura asociada.'], 422);
            }
            if (
                ! $esAdministrador
                && $citasGrupo->contains(fn ($citaGrupo) => (int) $citaGrupo->Usuario_idUsuarioBar !== (int) $usuario->idUsuario)
            ) {
                return response()->json(['message' => 'No tienes permiso para registrar el pago de citas asignadas a otro barbero.'], 403);
            }

            $factura = $this->crearFacturaGrupo($citasGrupo, $datos['metodo_pago']);

            return response()->json([
                'message' => 'Pago registrado y factura creada para las citas consecutivas.',
                'factura' => $factura,
                'cita' => $cita->load(['cliente', 'barbero', 'servicios', 'factura', 'facturas']),
            ]);
        });
    }

    private function citasConsecutivas(Citas $cita): Collection
    {
        $consulta = Citas::with('servicios')
            ->whereDate('Fecha_hora', (new DateTimeImmutable($cita->Fecha_hora))->format('Y-m-d'))
            ->where('modalidad_pago', $cita->modalidad_pago)
            ->where('metodo_pago', $cita->metodo_pago)
            ->where('estado_pago', 'Pendiente')
            ->whereIn('estado', ['Pendiente', 'Confirmado', 'Finalizado']);

        if ($cita->Usuario_idUsuarioCli) {
            $consulta->where('Usuario_idUsuarioCli', $cita->Usuario_idUsuarioCli);
        } else {
            $consulta->whereNull('Usuario_idUsuarioCli')
                ->where('nombre_cliente', $cita->nombre_cliente)
                ->where('telefono_cliente', $cita->telefono_cliente);
        }

        $citas = $consulta->orderBy('Fecha_hora')->orderBy('idCita')->lockForUpdate()->get();
        $indice = $citas->search(fn ($citaGrupo) => (int) $citaGrupo->idCita === (int) $cita->idCita);
        if ($indice === false) {
            return new Collection([$cita]);
        }

        $inicio = $indice;
        while ($inicio > 0 && $this->finCita($citas[$inicio - 1]) == new DateTimeImmutable($citas[$inicio]->Fecha_hora)) {
            $inicio--;
        }

        $fin = $indice;
        while (
            $fin + 1 < $citas->count()
            && $this->finCita($citas[$fin]) == new DateTimeImmutable($citas[$fin + 1]->Fecha_hora)
        ) {
            $fin++;
        }

        return $citas->slice($inicio, $fin - $inicio + 1)->values();
    }

    private function finCita(Citas $cita): DateTimeImmutable
    {
        $servicios = $this->serviciosProgramados($cita);
        if ($servicios === []) {
            return (new DateTimeImmutable($cita->Fecha_hora))->modify('+60 minutes');
        }

        return $servicios[count($servicios) - 1]['fin'];
    }

    private function crearFacturaGrupo(Collection $citas, string $metodoPago): Factura
    {
        $primeraCita = $citas->first();
        $total = $citas->sum(
            fn ($citaGrupo) => $citaGrupo->servicios->sum(fn ($servicio) => $this->precioServicio($servicio))
        );

        $factura = Factura::create([
            'numero_factura' => 'TEMP',
            'fecha_emision' => now(),
            'subtotal' => $total,
            'total_pagar' => $total,
            'metodo_pago' => $metodoPago,
            'estado_factura' => 'Pagada',
            'Cita_idCita' => $primeraCita->idCita,
            'Usuario_idUsuario' => $primeraCita->Usuario_idUsuarioCli,
        ]);
        $factura->citas()->sync($citas->pluck('idCita'));
        Citas::whereIn('idCita', $citas->pluck('idCita'))->update(['estado_pago' => 'Pagado']);

        return $factura;
    }

    private function actualizarTotalesFacturaCitas(Citas $cita): void
    {
        $facturas = $cita->facturas()->with('citas.servicios')->get();
        foreach ($facturas as $factura) {
            $total = $factura->citas->sum(
                fn ($citaFactura) => $citaFactura->servicios->sum(fn ($servicio) => $this->precioServicio($servicio))
            );
            $factura->update(['subtotal' => $total, 'total_pagar' => $total]);
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
        if (
            $esCliente
            && $request->has('estado')
            && $request->input('estado') !== 'Cancelado'
        ) {
            return response()->json(['result' => 'error', 'message' => 'No puedes cambiar el estado de la cita directamente.'], 403);
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
            return response()->json(['result' => 'error', 'message' => 'Solo puedes modificar o cancelar la cita hasta dos horas antes de su inicio.'], 422);
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

                return response()->json(['result' => 'error', 'message' => 'No puedes modificar o cancelar esta cita. Debes hacerlo al menos dos horas antes.'], 422);
            }

            $dataToUpdate = $request->only(['Usuario_idUsuarioBar', 'estado']);
            if ($modificaProgramacion && ! $cancelaCita) {
                $dataToUpdate['fecha_agendada'] = now();
                $dataToUpdate['estado'] = 'Confirmado';
                $dataToUpdate['confirmacion_solicitada_at'] = null;
                DB::table('notificacion')
                    ->where('Cita_idCita', $cita->idCita)
                    ->where('evento', 'confirmacion')
                    ->whereNotNull('token_confirmacion')
                    ->update(['token_confirmacion' => null]);
            }
            $fechaAnterior = $cita->Fecha_hora;
            if ($request->has('Fecha_hora') && $request->input('Fecha_hora') !== $fechaAnterior) {
                $dataToUpdate['Fecha_hora'] = $request->input('Fecha_hora');
                $dataToUpdate['fecha_agendada'] = now();
                $dataToUpdate['estado'] = 'Confirmado';
                $dataToUpdate['confirmacion_solicitada_at'] = null;
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
                    $serviciosAnteriores = $cita->servicios;
                } else {
                    $servicios = collect($this->serviciosProgramados($cita))->pluck('servicio');
                    $idsServicios = $servicios->pluck('idServicio')->all();
                    $serviciosAnteriores = collect();
                }

                if ($servicios->isEmpty()) {
                    DB::rollBack();

                    return response()->json(['result' => 'error', 'message' => 'La cita debe tener al menos un servicio.'], 422);
                }

                $inicioOriginal = $this->inicioCita($cita);
                $desplazamientos = [];
                foreach ($this->serviciosProgramados($cita) as $programado) {
                    $desplazamientos[$programado['servicio']->idServicio][] = (int) (
                        ($programado['inicio']->getTimestamp() - $inicioOriginal->getTimestamp()) / 60
                    );
                }
                $desplazamientoSiguiente = 0;
                $intervalosPorFecha = [];
                $horariosServicios = [];
                foreach ($servicios as $servicio) {
                    $servicioAnterior = $serviciosAnteriores->firstWhere('idServicio', $servicio->idServicio);
                    $datosHistoricos = $this->datosHistoricosServicio($servicioAnterior ?? $servicio);
                    $desplazamientos[$servicio->idServicio] ??= [];
                    $desplazamiento = array_shift($desplazamientos[$servicio->idServicio]) ?? $desplazamientoSiguiente;
                    $inicioServicio = $inicioCita->modify('+'.$desplazamiento.' minutes');
                    $finServicio = $inicioServicio->modify('+'.max(1, $datosHistoricos['duracion_servicio']).' minutes');
                    if (! $this->estaDentroDelHorario($barbero, $inicioServicio, $finServicio)) {
                        DB::rollBack();

                        return response()->json(['result' => 'error', 'message' => 'La cita queda fuera del horario laboral del barbero.'], 422);
                    }

                    $fechaServicio = $inicioServicio->format('Y-m-d');
                    if (! array_key_exists($fechaServicio, $intervalosPorFecha)) {
                        $intervalosPorFecha[$fechaServicio] = $this->obtenerIntervalosOcupados(
                            $barbero->idUsuario,
                            $fechaServicio,
                            (int) $cita->idCita,
                        );
                    }

                    if (! $this->intervaloDisponible($inicioServicio, $finServicio, $intervalosPorFecha[$fechaServicio])) {
                        DB::rollBack();

                        return response()->json(['result' => 'error', 'message' => 'Ese horario ya está ocupado. Elige otro horario.'], 422);
                    }

                    $intervalosPorFecha[$fechaServicio][] = [$inicioServicio, $finServicio];
                    $horariosServicios[] = [
                        'idServicio' => $servicio->idServicio,
                        'fecha_hora_servicio' => $inicioServicio->format('Y-m-d H:i:s'),
                    ] + $datosHistoricos;
                    $desplazamientoSiguiente = max(
                        $desplazamientoSiguiente,
                        $desplazamiento + max(1, $datosHistoricos['duracion_servicio'])
                    );
                }
                $inicioMasTemprano = collect($horariosServicios)
                    ->map(fn ($horario) => new DateTimeImmutable($horario['fecha_hora_servicio']))
                    ->sortBy(fn ($inicio) => $inicio->getTimestamp())
                    ->first();
                if ($inicioMasTemprano) {
                    $dataToUpdate['Fecha_hora'] = $inicioMasTemprano->format('Y-m-d H:i:s');
                }
            }

            $cita->update($dataToUpdate);

            if ($request->has('servicios')) {
                $cita->servicios()->detach();
                foreach ($horariosServicios as $horarioServicio) {
                    $idServicio = $horarioServicio['idServicio'];
                    unset($horarioServicio['idServicio']);
                    $cita->servicios()->attach($idServicio, $horarioServicio);
                }
                if ($cita->facturas()->exists()) {
                    $this->actualizarTotalesFacturaCitas($cita);
                }
            } elseif ($horariosServicios !== null) {
                $cita->servicios()->detach();
                foreach ($horariosServicios as $horarioServicio) {
                    $idServicio = $horarioServicio['idServicio'];
                    unset($horarioServicio['idServicio']);
                    $cita->servicios()->attach($idServicio, $horarioServicio);
                }
            }

            if (isset($dataToUpdate['estado']) && $dataToUpdate['estado'] === 'Cancelado' && $cita->facturas()->exists()) {
                $cita->facturas()->update(['estado_factura' => 'Anulada']);
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
            $puedeCancelarDuranteConfirmacion = $cita->estado === 'Pendiente'
                && $cita->confirmacion_solicitada_at
                && $cita->confirmacion_solicitada_at->copy()->addMinutes(30)->gt(now())
                && new DateTimeImmutable($cita->Fecha_hora) > new DateTimeImmutable;
            if (
                in_array($cita->estado, ['Cancelado', 'Finalizado'], true)
                || (! $this->estaDentroDelPlazoMinimo($cita) && ! $puedeCancelarDuranteConfirmacion)
            ) {
                return response()->json(['result' => 'error', 'message' => 'Solo puedes cancelar la cita hasta dos horas antes de su inicio.'], 422);
            }
        }

        $cita->estado = 'Cancelado';
        $cita->confirmacion_solicitada_at = null;
        $cita->save();
        DB::table('notificacion')
            ->where('Cita_idCita', $cita->idCita)
            ->where('evento', 'confirmacion')
            ->whereNotNull('token_confirmacion')
            ->update(['token_confirmacion' => null]);
        $cita->facturas()->update(['estado_factura' => 'Anulada']);

        return response()->json(['result' => 'ok', 'message' => 'cita eliminada']);
    }

    private function estaDentroDelPlazoMinimo(Citas $cita): bool
    {
        return $this->inicioCita($cita) > now()->addHours(2)->toDateTimeImmutable();
    }

    private function inicioCita(Citas $cita): DateTimeImmutable
    {
        $servicios = $this->serviciosProgramados($cita);

        return $servicios[0]['inicio'] ?? new DateTimeImmutable($cita->Fecha_hora);
    }

    private function serviciosProgramados(Citas $cita): array
    {
        $servicios = $cita->relationLoaded('servicios')
            ? $cita->servicios
            : $cita->servicios()->get();
        $siguiente = new DateTimeImmutable($cita->Fecha_hora);
        $programados = [];

        foreach ($servicios as $servicio) {
            $inicio = $servicio->pivot->fecha_hora_servicio
                ? new DateTimeImmutable($servicio->pivot->fecha_hora_servicio)
                : $siguiente;
            $fin = $inicio->modify('+'.max(1, $this->duracionServicio($servicio)).' minutes');
            $programados[] = compact('servicio', 'inicio', 'fin');
            $siguiente = $fin;
        }

        return array_values(
            collect($programados)
                ->sortBy(fn ($programado) => $programado['inicio']->getTimestamp())
                ->all()
        );
    }

    private function duracionServicio(Servicio $servicio): int
    {
        return (int) ($servicio->pivot?->duracion_servicio ?? $servicio->Duracion);
    }

    private function precioServicio(Servicio $servicio): float
    {
        return (float) ($servicio->pivot?->precio_servicio ?? $servicio->Precio);
    }

    private function datosHistoricosServicio(Servicio $servicio): array
    {
        return [
            'nombre_servicio' => $servicio->pivot?->nombre_servicio ?? $servicio->Nombre,
            'duracion_servicio' => $this->duracionServicio($servicio),
            'precio_servicio' => $this->precioServicio($servicio),
        ];
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
                    $fin = $inicio->modify('+'.max(1, $this->duracionServicio($servicio)).' minutes');
                    $intervalos[] = [$inicio, $fin];
                }

                continue;
            }

            $inicio = new DateTimeImmutable($cita->Fecha_hora);
            $duracion = $cita->servicios->sum(fn ($servicio) => $this->duracionServicio($servicio));
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

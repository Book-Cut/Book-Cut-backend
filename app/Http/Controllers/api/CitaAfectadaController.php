<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Citas;
use App\Models\citaAfectada;
use App\Models\notificacionWeb;
use App\Models\usuario;
use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CitaAfectadaController extends Controller
{
    private GestionCitasController $g;

    public function __construct()
    {
        $this->g = app(GestionCitasController::class);
    }

    public function cancelar(Request $request, int $id)
    {
        return $this->resolver($request, $id, function (Citas $cita) {
            $cita->estado = 'Cancelado';
            $cita->confirmacion_solicitada_at = null;
            $cita->save();
            DB::table('notificacion')->where('Cita_idCita', $cita->idCita)
                ->where('evento', 'confirmacion')->whereNotNull('token_confirmacion')
                ->update(['token_confirmacion' => null]);
            $cita->facturas()->update(['estado_factura' => 'Anulada']);

            return 'Cancelada';
        });
    }

    public function horarios(Request $request, int $id)
    {
        $fecha = $request->validate(['fecha' => 'required|date_format:Y-m-d|after_or_equal:today'])['fecha'];
        $cita = $this->cargar($request, $id);
        if (!$cita) {
            return response()->json(['message' => 'No encontrada.'], 404);
        }

        $barbero = usuario::with('horariosLaborales')->find($cita->Usuario_idUsuarioBar);
        $dia = new DateTimeImmutable($fecha);
        $horas = [];
        foreach ($barbero->horariosLaborales as $h) {
            if (!$this->g->horarioCorrespondeAlDia($h->diasemana, $dia)) {
                continue;
            }
            $fin = new DateTimeImmutable("$fecha $h->horafin");
            for ($i = new DateTimeImmutable("$fecha $h->horainicio"); $i->modify('+30 minutes') <= $fin; $i = $i->modify('+30 minutes')) {
                if ($i > new DateTimeImmutable && $this->planificar($cita, $barbero, $i)) {
                    $horas[$i->format('H:i')] = $i->format('H:i');
                }
            }
        }
        ksort($horas);

        return response()->json(['data' => array_values($horas)]);
    }

    public function reagendar(Request $request, int $id)
    {
        $d = $request->validate([
            'fecha' => 'required|date_format:Y-m-d|after_or_equal:today',
            'hora' => 'required|date_format:H:i',
        ]);

        return $this->resolver($request, $id, function (Citas $cita) use ($d) {
            $inicio = new DateTimeImmutable("{$d['fecha']} {$d['hora']}");
            $barbero = usuario::with('horariosLaborales')->find($cita->Usuario_idUsuarioBar);
            $plan = $inicio > new DateTimeImmutable ? $this->planificar($cita, $barbero, $inicio) : null;
            if (!$plan) {
                return response()->json(['message' => 'Ese horario no está disponible. Elige otro.'], 422);
            }
            $this->aplicar($cita, $barbero->idUsuario, $plan);

            return 'Reagendada';
        });
    }

    public function barberosAlternativos(Request $request, int $id)
    {
        $cita = $this->cargar($request, $id);
        if (!$cita) {
            return response()->json(['message' => 'No encontrada.'], 404);
        }
        $inicio = $this->g->inicioCita($cita);
        $data = $this->alternativos($cita)
            ->filter(fn ($b) => $this->planificar($cita, $b, $inicio) !== null)
            ->map(fn ($b) => ['idUsuario' => $b->idUsuario, 'Nombre' => $b->Nombre])
            ->values();

        return response()->json(['data' => $data]);
    }

    public function otroBarbero(Request $request, int $id)
    {
        $d = $request->validate(['barbero_id' => 'required|integer']);

        return $this->resolver($request, $id, function (Citas $cita) use ($d) {
            $barbero = $this->alternativos($cita)->firstWhere('idUsuario', $d['barbero_id']);
            $plan = $barbero ? $this->planificar($cita, $barbero, $this->g->inicioCita($cita)) : null;
            if (!$plan) {
                return response()->json(['message' => 'Ese barbero no está disponible a la misma hora.'], 422);
            }
            $this->aplicar($cita, $barbero->idUsuario, $plan);

            return 'OtroBarbero';
        });
    }



    private function cargar(Request $request, int $id, bool $lock = false): ?Citas
    {
        $a = $lock ? citaAfectada::lockForUpdate()->find($id) : citaAfectada::find($id);
        if (!$a || $a->respuesta !== 'Pendiente') {
            return null;
        }
        $q = Citas::with('servicios');
        $cita = ($lock ? $q->lockForUpdate() : $q)->find($a->Cita_idCita);

        if (
            !$cita
            || (int) $cita->Usuario_idUsuarioCli !== (int) $request->user()->idUsuario
            || !in_array($cita->estado, ['Pendiente', 'Confirmado'], true)
            || $this->g->inicioCita($cita) <= new DateTimeImmutable
        ) {
            return null;
        }

        return $cita;
    }

    private function resolver(Request $request, int $id, callable $fn)
    {
        return DB::transaction(function () use ($request, $id, $fn) {
            $cita = $this->cargar($request, $id, true);
            if (!$cita) {
                return response()->json(['message' => 'Esta cita ya no admite respuesta.'], 422);
            }
            $res = $fn($cita);
            if ($res instanceof JsonResponse) {
                return $res;
            }
            citaAfectada::where('idCitaAfectada', $id)->update(['respuesta' => $res, 'respondida_at' => now()]);
            notificacionWeb::where('CitaAfectada_id', $id)->update(['leida' => true]);

            return response()->json([
                'message' => 'Respuesta registrada.',
                'cita' => $cita->fresh(['cliente', 'barbero', 'servicios']),
            ]);
        });
    }


    private function planificar(Citas $cita, usuario $barbero, DateTimeImmutable $inicio): ?array
    {
        $base = $this->g->inicioCita($cita);
        $ocupados = [];
        $plan = [];
        foreach ($this->g->serviciosProgramados($cita) as $p) {
            $min = (int) (($p['inicio']->getTimestamp() - $base->getTimestamp()) / 60);
            $ini = $inicio->modify("+$min minutes");
            $fin = $ini->modify('+' . max(1, $this->g->duracionServicio($p['servicio'])) . ' minutes');
            $f = $ini->format('Y-m-d');
            $ocupados[$f] ??= $this->g->obtenerIntervalosOcupados($barbero->idUsuario, $f, $cita->idCita);
            if (
                !$this->g->estaDentroDelHorario($barbero, $ini, $fin)
                || !$this->g->intervaloDisponible($ini, $fin, $ocupados[$f])
            ) {
                return null;
            }
            $ocupados[$f][] = [$ini, $fin];
            $plan[] = [$p['servicio'], $ini];
        }

        return $plan ?: null;
    }

    private function aplicar(Citas $cita, int $barberoId, array $plan): void
    {
        $cita->Usuario_idUsuarioBar = $barberoId;
        $cita->Fecha_hora = $plan[0][1]->format('Y-m-d H:i:s');
        $cita->fecha_agendada = now();
        $cita->estado = 'Confirmado';
        $cita->confirmacion_solicitada_at = null;
        $cita->save();
        DB::table('notificacion')->where('Cita_idCita', $cita->idCita)
            ->where('evento', 'confirmacion')->whereNotNull('token_confirmacion')
            ->update(['token_confirmacion' => null]);

        $filas = array_map(fn ($p) => [
            $p[0]->idServicio,
            ['fecha_hora_servicio' => $p[1]->format('Y-m-d H:i:s')] + $this->g->datosHistoricosServicio($p[0]),
        ], $plan);
        $cita->servicios()->detach();
        foreach ($filas as [$idServicio, $pivot]) {
            $cita->servicios()->attach($idServicio, $pivot);
        }
    }

    private function alternativos(Citas $cita)
    {
        $ids = $cita->servicios->pluck('idServicio')->unique()->values();

        return usuario::with('horariosLaborales')
            ->where('Roles_IDRol', 2)
            ->where('idUsuario', '!=', $cita->Usuario_idUsuarioBar)
            ->whereHas('especialidad', fn ($q) => $q->whereIn('servicio.idServicio', $ids), '=', $ids->count())
            ->get();
    }
}

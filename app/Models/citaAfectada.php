<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;

class citaAfectada extends Model
{
    public $table = 'cita_afectada';
    public $primaryKey = 'idCitaAfectada';
    public $timestamps = false;
    public $fillable = ['Cita_idCita','Indisponibilidad_id','respuesta','respondida_at'];

    public function indisponibilidad()
    {
        return $this->belongsTo(indisponibilidad::class, 'Indisponibilidad_id', 'idIndisponibilidad');
    }

    //marca la cita como afectada
    public static function avisar(Citas $cita, int $idIndisponibilidad, ?string $motivo): bool
    {
        $afectada = self::create([
            'Cita_idCita' => $cita->idCita,
            'Indisponibilidad_id' => $idIndisponibilidad,
        ]);

        notificacionWeb::create([
            'Usuario_idUsuario' => $cita->Usuario_idUsuarioCli,
            'CitaAfectada_id' => $afectada->idCitaAfectada,
            'titulo' => 'Tu cita fue afectada',
            'mensaje' => "Tu cita del {$cita->Fecha_hora} no podrá realizarse. Reagéndala o cancélala en Mis citas.",
        ]);

        try {
            Mail::send('correos.cita-afectada', [
                'nombreCliente' => $cita->cliente->Nombre,
                'fecha' => substr($cita->Fecha_hora, 0, 10),
                'hora' => substr($cita->Fecha_hora, 11, 5),
                'motivo' => $motivo,
                'enlace' => rtrim(explode('#', config('app.frontend_url'))[0], '/') . '/#/mis-citas',
            ], fn ($m) => $m->to($cita->cliente->correo)->subject('Tu cita en Book&Cut necesita tu respuesta'));

            return true;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }
}
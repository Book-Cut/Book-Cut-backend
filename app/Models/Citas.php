<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Citas extends Model
{
    //
    public $primaryKey = 'cita_id';
    public $fillable = [
        'idCita',
        'fecha_hora',
        'estado',
        'Usuario_idUsuarioCli',
        'estado_cita_id',
        'Valora_idvalora',
        'servicio_idServicio',
        'barbero_idbarbero',
    ];

    public $timestamps = false;

    public $table = 'cita';

}
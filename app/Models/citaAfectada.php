<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class citaAfectada extends Model
{
    public $table = 'cita_afectada';
    public $primaryKey = 'idCitaAfectada';
    public $timestamps = false;
    public $fillable = ['Cita_idCita','Indisponibilidad_id','respuesta','respondida_at'];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Valora extends Model
{
    //
    public $primaryKey = 'idvalora';

    public $fillable = [
        'Idvalora',
        'Evaluacion_(Barbero)_idEvaluacion_Bar',
        'Evaluacion_(Cliente)_idEvaluacion_cli',
        'Fecha_evaluacion_bar',
        'Fecha_evaluacion_cli',
    ];
    public $timestamps = false;
    public $table = 'valora';

    public function cita()
    {
        return $this->hasOne(Citas::class, 'Valora_Idvalora', 'Idvalora');
    }
}

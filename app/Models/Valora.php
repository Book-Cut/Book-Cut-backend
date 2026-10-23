<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Valora extends Model
{
    //
    public $primaryKey = 'Idvalora';

    public $fillable = [
        'Idvalora',
        'EvaluacionBar',
        'EvaluacionCita',
        'Comentario',
        'Fecha_evaluacion_bar',
        'Fecha_evaluacion_cita',
    ];
    public $timestamps = false;
    public $table = 'valora';

    public function cita()
    {
        return $this->hasOne(Citas::class, 'Valora_Idvalora', 'Idvalora');
    }

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Citas extends Model
{
    //
    public $primaryKey = 'idCita';
    public $fillable = [
        'idCita',
        'Fecha_hora',
        'estado',
        'Usuario_idUsuarioCli',
        'Usuario_idUsuarioBar',
        'Valora_Idvalora',
        'Servicio_idServicio',
    ];

    public $timestamps = false;

    public $table = 'cita';

    public function servicios()
    {
        return $this->belongsTo(Servicio::class, 'Servicio_idServicio', 'idServicio');
    }
    public function getFactura()
    {
        return $this->hasOne(Factura::class, 'Cita_idCita', 'idCita');
    }

}
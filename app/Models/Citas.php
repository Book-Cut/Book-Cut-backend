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
        "Valora_Idvalora",
        'Usuario_idUsuarioCli',
        'Usuario_idUsuarioBar',
        'Valora_Idvalora',
        'Servicio_idServicio',
    ];

    public $timestamps = false;

    public $table = 'cita';

    public function valora()
    {
        return $this->belongsTo(Valora::class, 'Valora_Idvalora', 'Idvalora');
    }

    public function servicios()
    {
        return $this->belongsTo(Servicio::class, 'Servicio_idServicio', 'idServicio');
    }
    public function getFactura()
    {
        return $this->hasOne(Factura::class, 'Cita_idCita', 'idCita');
    }

    public function getValora()
    {
        return $this->belongsTo(Valora::class, 'Valora_Idvalora', 'idvalora');
    }

    public function getBarbero()
    {
        return $this->belongsTo(usuario::class, 'Usuario_idUsuarioBar', 'idUsuario');
    }

    public function getCliente()
    {
        return $this->belongsTo(usuario::class, 'Usuario_idUsuarioCli', 'idUsuario');
    }

}
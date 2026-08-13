<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Citas extends Model
{
    public $table = 'cita';
    public $primaryKey = 'idCita';
    public $timestamps = false;

    
    public $fillable = [
        'Fecha_hora',
        'estado',
        'Valora_Idvalora',
        'Usuario_idUsuarioCli',
        'Usuario_idUsuarioBar',
    ];

    public function servicios()
    {
        return $this->belongsToMany(Servicio::class, 'cita_servicio', 'idCita', 'idServicio');
    }

    public function valora()
    {
        return $this->belongsTo(Valora::class, 'Valora_Idvalora', 'Idvalora');
    }

    public function factura()
    {
        return $this->hasOne(Factura::class, 'Cita_idCita', 'idCita');
    }

    public function barbero()
    {
        return $this->belongsTo(usuario::class, 'Usuario_idUsuarioBar', 'idUsuario');
    }

    public function cliente()
    {
        return $this->belongsTo(usuario::class, 'Usuario_idUsuarioCli', 'idUsuario');
    }
}
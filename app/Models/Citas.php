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

        return $this->belongsToMany(Servicio::class, 'cita_servicio', 'idCita', 'idServicio')
            ->withPivot('fecha_hora_servicio');
    }

    public function valora()
    {
        return $this->belongsTo(Valora::class, 'Valora_Idvalora', 'Idvalora');
    }

    public function cliente()
    {
        return $this->belongsTo(usuario::class, 'Usuario_idUsuarioCli', 'idUsuario');
    }

    public function barbero()
    {
        return $this->belongsTo(Usuario::class, 'Usuario_idUsuarioBar', 'idUsuario');
    }

    public function factura()
    {
        return $this->hasOne(factura::class, 'Cita_idCita', 'idCita');
    }

    public function getBarbero()
    {
        return $this->barbero();
    }

    public function getCliente()
    {
        return $this->cliente();
    }
}
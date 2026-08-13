<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Citas extends Model
{
    //
    public $table = 'cita';
    public $primaryKey = 'idCita';
    public $timestamps = false;

    public $fillable = [
        'Fecha_hora',
        'estado',
        'Valora_Idvalora',
        'Usuario_idUsuarioCli',
        'Usuario_idUsuarioBar',
        'Servicio_idServicio',
    ];


    public function valora()
    {
        return $this->belongsTo(Valora::class, 'Valora_Idvalora', 'Idvalora');
    }

    public function servicios()
    {
        return $this->belongsToMany(Servicio::class, 'cita_servicio', 'idCita', 'idServicio');
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

    public function usuario()
    {
        return $this->belongsTo(usuario::class, 'Usuario_idUsuarioCli', 'idUsuario');
    }

    public function getRoles()
    {
        return $this->belongsTo(roles::class, 'Roles_IDRol', 'iDRol');
    }

}
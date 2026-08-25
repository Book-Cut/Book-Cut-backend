<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class usuario extends Authenticatable
{
    use HasApiTokens, Notifiable;

    public $table = 'usuario';

    public $primaryKey = 'idUsuario';

    public $timestamps = false;

    protected $hidden = ['contrasenha'];

    public $fillable = [
        'Nombre',
        'correo',
        'telefono',
        'contrasenha',
        'especialidad',
        'disponibilidad',
        'horario',
        'Roles_IDRol',
    ];

    public function getAuthPassword()
    {
        return $this->contrasenha;
    }

    public function roles()
    {
        return $this->belongsTo(roles::class, 'Roles_IDRol', 'iDRol');
    }

    public function barbero()
    {
        return $this->hasMany(Citas::class, 'Usuario_idUsuarioBar', 'idUsuario');
    }

    public function cliente()
    {
        return $this->hasMany(Citas::class, 'Usuario_idUsuarioCli', 'idUsuario');
    }

    public function citas()
    {
        return $this->hasMany(Citas::class, 'Usuario_idUsuarioCli', 'idUsuario');
    }

    public function especialidad()
    {
        return $this->belongsToMany(Servicio::class, 'barbero_servicio_table', 'Usuario_idUsuarioBar', 'idServicio');
    }

    public function horario()
    {
        return $this->belongsToMany(horario::class, 'barbero_has_horario', 'Usuario_idUsuarioBar', 'horario_idhorario');
    }
}

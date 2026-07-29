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
        'Roles_IDRol'
    ];

    public function getAuthPassword()
    {
        return $this->contrasenha;
    }

    public function rol()
    {
        return $this->belongsTo(roles::class, 'roles_IDRol', 'iDRol');
    }
}

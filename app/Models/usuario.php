<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class usuario extends Authenticatable
{
    use HasApiTokens, Notifiable;

    public $table = 'Usuario';
    public $primaryKey = 'idUsuario';
    public $timestamps = false;

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
        return $this->belongsTo(Roles::class, 'Roles_IDRol', 'IDRol');
    }
}

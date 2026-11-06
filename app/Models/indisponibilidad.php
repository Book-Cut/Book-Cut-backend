<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class indisponibilidad extends Model
{
    protected $table = 'indisponibilidad';
    protected $primaryKey = 'idIndisponibilidad';
    public $timestamps = false;

    protected $fillable = [
        'Usuario_idUsuarioBar',
        'fecha',
        'hora_inicio',
        'hora_fin',
        'motivo',
        'Usuario_idUsuarioReg',
    ];
}

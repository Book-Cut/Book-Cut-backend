<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class beneficios extends Model
{
    //
    public $primaryKey = 'ID_publicacion';
    public $fillable = [
        'titulo',
        'Tipo_beneficio',
        'Fecha_inicio',
        'Fecha_fin',
        'Usuario_idUsuario',
    ];

    public $timestamps = false;

    public $table = 'beneficios';

}

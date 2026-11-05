<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class notificacionWeb extends Model
{
    public $table = 'notificacion_web';
    public $primaryKey = 'idNotificacionWeb';
    public $timestamps = false;
    public $fillable = ['Usuario_idUsuario','CitaAfectada_id','titulo','mensaje','leida'];
}

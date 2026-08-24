<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Servicio extends Model
{
    //
    public $primaryKey = 'idServicio';

    public $fillable = [
        'foto_perfil',
        'Nombre',
        'Duracion',
        'Precio',
    ];

    public $table = 'servicio';

    public $timestamps = false;

    public function rol()
    {
        return $this->belongsTo(roles::class, 'Roles_IDRol', 'iDRol');
    }



}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class usuario extends Model
{
    //
    public $primaryKey = 'idUsuario';
    
    public $fillable = [
        'Nombre',
        'correo',
        'telefono',
        "contrasena",
        "especialidad",
        "horario",
        'Roles_IDRol'
    ];

    public $table = 'usuario';
    
    public $timestamps = false;

    public function rol()
    {
        return $this->belongsTo(roles::class, 'Roles_IDRol', 'iDRol');
    }
    


}
    
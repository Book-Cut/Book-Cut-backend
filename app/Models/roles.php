<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class roles extends Model
{
    //
public $primaryKey = 'iDRol';

        public $fillable = [
        'Nombre_rol',
    ];

    public $timestamps = false;


     public function usuarios()
    {
        return $this->hasMany(usuario::class, 'Roles_IDRol', 'iDRol');
    }
}
 

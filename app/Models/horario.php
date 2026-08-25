<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class horario extends Model
{
    //
    public $primaryKey = 'idhorario';

    public $fillable = [
        'idhorario',
        'diasemana',
        'horainicio',
        'horafin',
    ];

    public $timestamps = false;

    public $table = 'horario';

    public function servicios()
    {
        return $this->hasMany(Servicio::class, 'idhorario');
    }
}

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
        'imagen',
        'Usuario_idUsuario',
    ];

    protected $appends = ['imagen_url'];

    public $timestamps = false;

    public $table = 'beneficio';

    public function getImagenUrlAttribute(){
        return $this->imagen ? asset('storage/' . $this->imagen) : null;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class perfil extends Model
{
    use HasFactory;
    protected $table = 'Perfil'; 
    protected $primaryKey = 'idPerfil';
    
    public $timestamps = false;

    protected $fillable = [
        'Ranking',
        'foto_perfil',
        'Usuario_idUsuario',
    ];

    public function usuario()
    {
        return $this->belongsTo(usuario::class, 'Usuario_idUsuario', 'idUsuario');
    }
}
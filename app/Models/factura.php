<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Factura extends Model
{
    public $primaryKey = 'factura_id';

    public $fillable = [
        'numero_factura',
        'fecha_emision',
        'Usuario_idUsuario',
        'subtotal',
        'total_pagar',
        'metodo_pago'
    ];

     public $table = 'factura';

    public $timestamps = false;

    public function usuario()
    {
        return $this->belongsTo(usuario::class, 'Usuario_idUsuario');
    }
}



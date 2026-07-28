<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Factura extends Model
{
    public $primaryKey = 'idfactura';

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

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($factura) {
            $factura->numero_factura = 'TEMP';
        });

        static::created(function ($factura) {
            $factura->numero_factura = 'FAC-' . str_pad($factura->idfactura, 3, '0', STR_PAD_LEFT);
            $factura->save();
        });
    }

    public function usuario()
    {
        return $this->belongsTo(usuario::class, 'Usuario_idUsuario');
    }
}



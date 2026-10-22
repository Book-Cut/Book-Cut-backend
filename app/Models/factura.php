<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Factura extends Model
{
    public $primaryKey = 'idfactura';

    public $table = 'factura';

    public $timestamps = false;

    public $fillable = [
        'numero_factura',
        'fecha_emision',
        'subtotal',
        'total_pagar',
        'metodo_pago',
        'estado_factura',
        'Cita_idCita',
        'Usuario_idUsuario',
        'Beneficio_ID_promocion',

    ];

    protected $casts = [
        'subtotal' => 'float',
        'total_pagar' => 'float',
        'Cita_idCita' => 'integer',
        'Usuario_idUsuario' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($factura) {
            $factura->numero_factura = 'TEMP';
        });

        static::created(function ($factura) {
            $numero = $factura->idfactura;

            do {
                $numeroFactura = 'FAC-' . str_pad($numero, 3, '0', STR_PAD_LEFT);
                $numero++;
            } while (
                static::where('numero_factura', $numeroFactura)
                    ->where('idfactura', '!=', $factura->idfactura)
                    ->exists()
            );

            $factura->numero_factura = $numeroFactura;
            $factura->save();
        });
    }

    public function cita()
    {
        return $this->belongsTo(Citas::class, 'Cita_idCita', 'idCita');
    }

    public function beneficio()
    {
        return $this->belongsTo(beneficios::class, 'Beneficio_ID_promocion', 'ID_promocion');
    }

    public function usuario()
    {
        return $this->hasOneThrough(
            usuario::class,
            Citas::class,
            'idCita',
            'idUsuario',
            'Cita_idCita',
            'Usuario_idUsuarioCli',
        );
    }
}

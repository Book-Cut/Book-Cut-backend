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
        'Cita_idCita',
        'subtotal',
        'total_pagar',
        'metodo_pago',
        'Usuario_idUsuario',
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
            $factura->numero_factura = 'FAC-' . str_pad($factura->idfactura, 3, '0', STR_PAD_LEFT);
            $factura->save();
        });
    }


    public function cita()
    {
        return $this->belongsTo(Citas::class, 'Cita_idCita', 'idCita');
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
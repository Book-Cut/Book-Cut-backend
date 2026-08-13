<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class facturaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        \DB::table('factura')->insert([
            [
                'idfactura' => '1',
                'numero_factura' => 'FAC-001',
                'fecha_emision' => '2026-07-20',
                'subtotal' => 50000,
                'total_pagar' => 50000,
                'metodo_pago' => 'Efectivo',
                'estado_factura' => 'Emitida',
                'Cita_idCita' => 1,
                'Usuario_idUsuario' => 4,
            ],
            [
                'idfactura' => '2',
                'numero_factura' => 'FAC-002',
                'fecha_emision' => '2026-07-21',
                'subtotal' => 20000,
                'total_pagar' => 20000,
                'metodo_pago' => 'Nequi',
                'estado_factura' => 'Emitida',
                'Cita_idCita' => 2,
                'Usuario_idUsuario' => 5,
            ],
        ]);
    }
}

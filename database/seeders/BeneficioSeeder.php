<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BeneficioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        \DB::table('Beneficio')->insert([
            [
                'titulo' => 'Descuento de Apertura',
                'Tipo_beneficio' => 'Descuento 20%',
                'Fecha_inicio' => '2026-07-01',
                'Fecha_fin' => '2026-07-31',
                'Usuario_idUsuario' => 1,
            ],
            [
                'titulo' => 'Servicio de Lavado Gratis',
                'Tipo_beneficio' => 'Promocion 2x1 en Lavado de Cabello',
                'Fecha_inicio' => '2026-08-01',
                'Fecha_fin' => '2026-08-31',
                'Usuario_idUsuario' => 2,
            ],
            [
                'titulo' => 'Programa de Referidos',
                'Tipo_beneficio' => 'Invita a un amigo y recibirán un descuento.',
                'Fecha_inicio' => '2026-09-01',
                'Fecha_fin' => '2026-09-30',
                'Usuario_idUsuario' => 3,
            ],
        ]);
    }
}

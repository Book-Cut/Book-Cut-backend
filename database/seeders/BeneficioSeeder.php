<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BeneficioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $administrador = DB::table('usuario')->where('correo', 'admin@bookcut.com')->value('idUsuario');

        foreach ([
            [
                'titulo' => 'Descuento de temporada',
                'Tipo_beneficio' => 'Descuento del 20% en servicios',
                'Fecha_inicio' => now()->subDays(7)->toDateString(),
                'Fecha_fin' => now()->addDays(30)->toDateString(),
            ],
            [
                'titulo' => 'Promoción de referidos',
                'Tipo_beneficio' => 'Descuento por referidos',
                'Fecha_inicio' => now()->addDays(7)->toDateString(),
                'Fecha_fin' => now()->addDays(45)->toDateString(),
            ],
        ] as $beneficio) {
            DB::table('beneficio')->updateOrInsert(
                [
                    'titulo' => $beneficio['titulo'],
                    'Usuario_idUsuario' => $administrador,
                ],
                [...$beneficio, 'Usuario_idUsuario' => $administrador],
            );
        }
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BarberoServicioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $barberos = DB::table('Usuario')
            ->whereIn('correo', ['carlos@bookcut.com', 'andres@bookcut.com'])
            ->pluck('idUsuario', 'correo');
        $servicios = DB::table('servicio')->pluck('idServicio', 'Nombre');

        foreach ($barberos as $idBarbero) {
            foreach ($servicios as $idServicio) {
                DB::table('barbero_servicio_table')->updateOrInsert(
                    [
                        'Usuario_idUsuarioBar' => $idBarbero,
                        'idServicio' => $idServicio,
                    ],
                    [
                        'Usuario_idUsuarioBar' => $idBarbero,
                        'idServicio' => $idServicio,
                    ],
                );
            }
        }
    }
}

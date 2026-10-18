<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (['Administrador', 'Barbero', 'Cliente'] as $nombreRol) {
            DB::table('roles')->updateOrInsert(
                ['Nombre_rol' => $nombreRol],
                ['Nombre_rol' => $nombreRol],
            );
        }
    }
}

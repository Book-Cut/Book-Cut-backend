<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        \DB::table('roles')->insert([
            [
                'nombre_rol' => 'Administrador',
            ],
            [
                'nombre_rol' => 'Barbero',
            ],
            [
                'nombre_rol' => 'Cliente',
            ],
        ]);
    }
}

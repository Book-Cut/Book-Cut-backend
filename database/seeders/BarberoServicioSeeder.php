<?php

namespace Database\Seeders;

use DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BarberoServicioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        DB::table('barbero_servicio_table')->insert([
            ['Usuario_idUsuarioBar' => 2, 'idServicio' => 1],
            ['Usuario_idUsuarioBar' => 3, 'idServicio' => 2],
            ['Usuario_idUsuarioBar' => 2, 'idServicio' => 3],
            ['Usuario_idUsuarioBar' => 3, 'idServicio' => 1],
            ['Usuario_idUsuarioBar' => 2, 'idServicio' => 2],
            ['Usuario_idUsuarioBar' => 3, 'idServicio' => 3],
        ]);
    }
}

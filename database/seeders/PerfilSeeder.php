<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PerfilSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        \DB::table('Perfil')->insert([
            [
                'Ranking' => 5,
                'foto_perfil' => 'luis.jpg',
                'Usuario_idUsuario' => 2,
            ],
            [
                'Ranking' => 4,
                'foto_perfil' => 'jesus.jpg',
                'Usuario_idUsuario' => 3,
            ],
            [
                'Ranking' => 3,
                'foto_perfil' => 'maria.jpg',
                'Usuario_idUsuario' => 4,
            ],
        ]);
    }
}

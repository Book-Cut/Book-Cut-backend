<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class barbero_has_horarioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        \DB::table('barbero_has_horario')->insert([
            [
                'horario_idhorario' => 1,
                'Usuario_idUsuarioBar' => 2,
            ],
            [
                'horario_idhorario' => 2,
                'Usuario_idUsuarioBar' => 2,
            ],
            [
                'horario_idhorario' => 3,
                'Usuario_idUsuarioBar' => 2,
            ],
            [
                'horario_idhorario' => 4,
                'Usuario_idUsuarioBar' => 2,
            ],
            [
                'horario_idhorario' => 5,
                'Usuario_idUsuarioBar' => 2,
            ],
            [
                'horario_idhorario' => 6,
                'Usuario_idUsuarioBar' => 2,
            ],
            [
                'horario_idhorario' => 2,
                'Usuario_idUsuarioBar' => 3,
            ],
            [
                'horario_idhorario' => 3,
                'Usuario_idUsuarioBar' => 3,
            ],
            [
                'horario_idhorario' => 4,
                'Usuario_idUsuarioBar' => 3,
            ],
            [
                'horario_idhorario' => 5,
                'Usuario_idUsuarioBar' => 3,
            ],
            [
                'horario_idhorario' => 6,
                'Usuario_idUsuarioBar' => 3,
            ],
        ]);
    }
}

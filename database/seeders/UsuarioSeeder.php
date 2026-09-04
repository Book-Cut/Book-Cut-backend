<?php

namespace Database\Seeders;

use DB;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsuarioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        DB::table('usuario')->insert([
            [
                'foto_perfil' => 'admin.jpg',
                'Nombre' => 'Juan Perez',
                'correo' => 'admin@bookcut.com',
                'telefono' => '3001112233',
                'contrasenha' => Hash::make('admin123'),
                'especialidad' => 'null',
                'horario' => 'null',
                'Roles_IDRol' => 1,
            ],
            [
                'foto_perfil' => 'barbero1.jpg',
                'Nombre' => 'Carlos Gomez',
                'correo' => 'carlos@bookcut.com',
                'telefono' => '3102223344',
                'contrasenha' => Hash::make('barbero123'),
                'especialidad' => 'Cortes Clasicos',
                'horario' => '1',
                'Roles_IDRol' => 2,
            ],
            [
                'foto_perfil' => 'barbero2.jpg',
                'Nombre' => 'Andres Felipe',
                'correo' => 'andres@bookcut.com',
                'telefono' => '3203334455',
                'contrasenha' => Hash::make('barbero456'),
                'especialidad' => 'Colorimetria y Barba',
                'horario' => '2',
                'Roles_IDRol' => 2,
            ],
            [
                'foto_perfil' => 'cliente1.jpg',
                'Nombre' => 'Luis Ramirez',
                'correo' => 'luis@gmail.com',
                'telefono' => '3154445566',
                'contrasenha' => Hash::make('cliente123'),
                'especialidad' => 'null',
                'horario' => 'null',
                'Roles_IDRol' => 3,
            ],
            [
                'foto_perfil' => 'cliente2.jpg',
                'Nombre' => 'Miguel Torres',
                'correo' => 'miguel@gmail.com',
                'telefono' => '3185556677',
                'contrasenha' => Hash::make('cliente456'),
                'especialidad' => 'null',
                'horario' => 'null',
                'Roles_IDRol' => 3,
            ],
        ]);

    }
}

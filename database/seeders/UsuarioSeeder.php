<?php

namespace Database\Seeders;

use DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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

                'Nombre' => 'Juan Perez',
                'correo' => 'admin@bookcut.com',
                'telefono' => '3001112233',
                'contrasenha' => Hash::make('admin123'),
                'especialidad' => 'No aplica',
                'disponibilidad' => 'No aplica',
                'horario' => 'No aplica',
                'Roles_IDRol' => 1
            ],
            [
                'Nombre' => 'Carlos Gomez',
                'correo' => 'carlos@bookcut.com',
                'telefono' => '3102223344',
                'contrasenha' => Hash::make('barbero123'),
                'especialidad' => 'Cortes Clasicos',
                'disponibilidad' => 'Lunes a Sabado',
                'horario' => '09:00:00',
                'Roles_IDRol' => 2
            ],
            [
                'Nombre' => 'Andres Felipe',
                'correo' => 'andres@bookcut.com',
                'telefono' => '3203334455',
                'contrasenha' => Hash::make('barbero456'),
                'especialidad' => 'Colorimetria y Barba',
                'disponibilidad' => 'Martes a Domingo',
                'horario' => '10:00:00',
                'Roles_IDRol' => 2
            ],
            [
                'Nombre' => 'Luis Ramirez',
                'correo' => 'luis@gmail.com',
                'telefono' => '3154445566',
                'contrasenha' => Hash::make('cliente123'),
                'especialidad' => 'No aplica',
                'disponibilidad' => 'No aplica',
                'horario' => 'No aplica',
                'Roles_IDRol' => 3
            ],
            [
                'Nombre' => 'Miguel Torres',
                'correo' => 'miguel@gmail.com',
                'telefono' => '3185556677',
                'contrasenha' => Hash::make('cliente456'),
                'especialidad' => 'No aplica',
                'disponibilidad' => 'No aplica',
                'horario' => 'No aplica',
                'Roles_IDRol' => 3
            ],
        ]);


    }
}

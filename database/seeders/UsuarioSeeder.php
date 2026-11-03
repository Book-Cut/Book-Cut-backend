<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsuarioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = DB::table('roles')->pluck('iDRol', 'Nombre_rol');
        $usuarios = [
            [
                'foto_perfil' => null,
                'Nombre' => 'Juan Perez',
                'correo' => 'admin@bookcut.com',
                'telefono' => '3001112233',
                'contrasenha' => 'admin123',
                'especialidad' => null,
                'horario' => null,
                'rol' => 'Administrador',
            ],
            [
                'foto_perfil' => null,
                'Nombre' => 'Carlos Gomez',
                'correo' => 'carlos@bookcut.com',
                'telefono' => '3102223344',
                'contrasenha' => 'barbero123',
                'especialidad' => 'Cortes Clasicos',
                'horario' => '1',
                'rol' => 'Barbero',
            ],
            [
                'foto_perfil' => null,
                'Nombre' => 'Andres Felipe',
                'correo' => 'andres@bookcut.com',
                'telefono' => '3203334455',
                'contrasenha' => 'barbero456',
                'especialidad' => 'Colorimetria y Barba',
                'horario' => '2',
                'rol' => 'Barbero',
            ],
            [
                'foto_perfil' => null,
                'Nombre' => 'Luis Ramirez',
                'correo' => 'luis@gmail.com',
                'telefono' => '3154445566',
                'contrasenha' => 'cliente123',
                'especialidad' => null,
                'horario' => null,
                'rol' => 'Cliente',
            ],
            [
                'foto_perfil' => null,
                'Nombre' => 'Miguel Torres',
                'correo' => 'miguel@gmail.com',
                'telefono' => '3185556677',
                'contrasenha' => 'cliente456',
                'especialidad' => null,
                'horario' => null,
                'rol' => 'Cliente',
            ],
            [
                'foto_perfil' => null,
                'Nombre' => 'Sofia Martinez',
                'correo' => 'sofia@gmail.com',
                'telefono' => '3196667788',
                'contrasenha' => 'cliente789',
                'especialidad' => null,
                'horario' => null,
                'rol' => 'Cliente',
            ],
        ];

        foreach ($usuarios as $usuario) {
            $rol = $usuario['rol'];
            unset($usuario['rol']);

            DB::table('Usuario')->updateOrInsert(
                ['correo' => $usuario['correo']],
                [
                    ...$usuario,
                    'Roles_IDRol' => $roles[$rol],
                    'contrasenha' => Hash::make($usuario['contrasenha']),
                ],
            );
        }
    }
}

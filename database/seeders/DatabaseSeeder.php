<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesSeeder::class,
            UsuarioSeeder::class,
            ServicioSeeder::class,
            HorarioSeeder::class,
            barbero_has_horarioSeeder::class,
            BarberoServicioSeeder::class,
            CitaSeeder::class,
            ValoraSeeder::class,
            facturaSeeder::class,
            notificacionSeeder::class,
            BeneficioSeeder::class,
        ]);
    }
}

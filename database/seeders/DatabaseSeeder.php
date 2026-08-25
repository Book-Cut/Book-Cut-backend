<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesSeeder::class,
            UsuarioSeeder::class,
            ServicioSeeder::class,
            HorarioSeeder::class,
            ValoraSeeder::class,
            BeneficioSeeder::class,

            barbero_has_horarioSeeder::class,
            CitaSeeder::class,
            Cita_servicioSeeder::class,
            facturaSeeder::class,
            notificacionSeeder::class,
            BarberoServicioSeeder::class,







        ]);
    }
}


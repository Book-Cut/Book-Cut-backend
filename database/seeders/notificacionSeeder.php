<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class notificacionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
         \DB::table('notificacion')->insert([

        [
            "tipo"=> "correo", 
            "mensaje"=> "Tu cita ha sido confirmada con exito", 
            "fecha_envio"=> "2026-07-19", 
            "Cita_idCita"=> 1
        ],
        [
            "tipo"=> "sms",
            "mensaje"=> "Recuerda tu cita de manhana",
            "fecha_envio"=> "2026-07-20",
            "Cita_idCita"=> 2
        ],
        [
            "tipo"=> "correo",
            "mensaje"=> "Tu cita esta pendiente de confirmacion por el barbero",
            "fecha_envio"=> "2026-07-22",
            "Cita_idCita"=> 3
        ]
        ]);
    }
}

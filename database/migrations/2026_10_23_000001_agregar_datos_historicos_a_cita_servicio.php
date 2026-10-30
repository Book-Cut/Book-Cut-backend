<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('cita_servicio', function (Blueprint $tabla) {
            $tabla->text('nombre_servicio')->nullable();
            $tabla->integer('duracion_servicio')->nullable();
            $tabla->integer('precio_servicio')->nullable();
        });

        /*DB::table('cita_servicio')
            ->join('servicio', 'servicio.idServicio', '=', 'cita_servicio.idServicio')
            ->update([
                'cita_servicio.nombre_servicio' => DB::raw('servicio.Nombre'),
                'cita_servicio.duracion_servicio' => DB::raw('servicio.Duracion'),
                'cita_servicio.precio_servicio' => DB::raw('servicio.Precio'),
            ]);*/
        DB::raw("UPDATE cita_servicio
SET
    nombre_servicio = servicio.Nombre,
    duracion_servicio = servicio.Duracion,
    precio_servicio = servicio.Precio
FROM servicio
WHERE servicio.idServicio = cita_servicio.idServicio;");
    }

    public function down(): void
    {
        Schema::table('cita_servicio', function (Blueprint $tabla) {
            $tabla->dropColumn([
                'nombre_servicio',
                'duracion_servicio',
                'precio_servicio',
            ]);
        });
    }
};

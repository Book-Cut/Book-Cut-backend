<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $indiceCita = collect(Schema::getIndexes('cita_servicio'))
            ->first(fn ($indice) => $indice['columns'] === ['idCita']);
        if (! $indiceCita) {
            Schema::table('cita_servicio', function (Blueprint $tabla) {
                $tabla->index('idCita');
            });
        }

        $indiceUnico = collect(Schema::getIndexes('cita_servicio'))
            ->first(fn ($indice) => $indice['unique'] && $indice['columns'] === ['idCita', 'idServicio']);
        if ($indiceUnico) {
            Schema::table('cita_servicio', function (Blueprint $tabla) use ($indiceUnico) {
                $tabla->dropUnique($indiceUnico['name']);
            });
        }

        DB::table('cita')
            ->where('modalidad_pago', 'En línea')
            ->where('estado_pago', 'Pendiente')
            ->update([
                'modalidad_pago' => 'Presencial',
                'metodo_pago' => null,
            ]);

        DB::table('cita')
            ->where('modalidad_pago', 'En línea')
            ->update(['modalidad_pago' => 'Presencial']);
    }

    public function down(): void
    {
        $duplicados = DB::table('cita_servicio')
            ->select('idCita', 'idServicio')
            ->groupBy('idCita', 'idServicio')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($duplicados) {
            throw new RuntimeException('No se puede restaurar la restricción: hay servicios repetidos dentro de citas.');
        }

        $indiceUnico = collect(Schema::getIndexes('cita_servicio'))
            ->first(fn ($indice) => $indice['unique'] && $indice['columns'] === ['idCita', 'idServicio']);
        if (! $indiceUnico) {
            Schema::table('cita_servicio', function (Blueprint $tabla) {
                $tabla->unique(['idCita', 'idServicio']);
            });
        }

        $indiceCita = collect(Schema::getIndexes('cita_servicio'))
            ->first(fn ($indice) => $indice['name'] === 'cita_servicio_idcita_index');
        if ($indiceCita) {
            Schema::table('cita_servicio', function (Blueprint $tabla) {
                $tabla->dropIndex('cita_servicio_idcita_index');
            });
        }
    }
};

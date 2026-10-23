<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cita', function (Blueprint $tabla) {
            $tabla->dateTime('fecha_agendada')->nullable()->after('confirmacion_solicitada_at');
            $tabla->index(['estado', 'Fecha_hora']);
        });

        Schema::table('notificacion', function (Blueprint $tabla) {
            $tabla->string('evento', 40)->nullable()->after('tipo');
            $tabla->dateTime('fecha_hora_cita')->nullable()->after('fecha_envio');
            $tabla->dateTime('enviado_at')->nullable()->after('fecha_hora_cita');
            $tabla->dateTime('ultimo_intento_at')->nullable()->after('enviado_at');
            $tabla->dateTime('leido_at')->nullable()->after('ultimo_intento_at');
            $tabla->string('token_confirmacion', 64)->nullable()->after('leido_at');
            $tabla->unsignedTinyInteger('intentos')->default(0)->after('token_confirmacion');
            $tabla->enum('estado_envio', ['Pendiente', 'Enviado', 'Error', 'No disponible'])
                ->default('Pendiente')
                ->after('intentos');
            $tabla->unique(
                ['Cita_idCita', 'evento', 'fecha_hora_cita'],
                'notificacion_cita_evento_horario_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('notificacion', function (Blueprint $tabla) {
            $tabla->dropUnique('notificacion_cita_evento_horario_unique');
            $tabla->dropColumn([
                'evento',
                'fecha_hora_cita',
                'enviado_at',
                'ultimo_intento_at',
                'leido_at',
                'token_confirmacion',
                'intentos',
                'estado_envio',
            ]);
        });

        Schema::table('cita', function (Blueprint $tabla) {
            $tabla->dropIndex(['estado', 'Fecha_hora']);
            $tabla->dropColumn('fecha_agendada');
        });
    }
};

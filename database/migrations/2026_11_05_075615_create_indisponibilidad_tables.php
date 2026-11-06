<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('indisponibilidad', function (Blueprint $table) {
            $table->id('idIndisponibilidad');
            $table->unsignedBigInteger('Usuario_idUsuarioBar');
            $table->date('fecha');
            $table->time('hora_inicio')->nullable();
            $table->time('hora_fin')->nullable();
            $table->string('motivo', 255)->nullable();
            $table->unsignedBigInteger('Usuario_idUsuarioReg');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('Usuario_idUsuarioBar')->references('idUsuario')->on('Usuario');
            $table->foreign('Usuario_idUsuarioReg')->references('idUsuario')->on('Usuario');
            $table->index(['Usuario_idUsuarioBar', 'fecha']);
        });

        Schema::create('cita_afectada', function (Blueprint $table) {
            $table->id('idCitaAfectada');
            $table->unsignedBigInteger('Cita_idCita');
            $table->unsignedBigInteger('Indisponibilidad_id');
            $table->string('respuesta', 20)->default('Pendiente');
            $table->timestamp('respondida_at')->nullable();

            $table->foreign('Cita_idCita')->references('idCita')->on('cita')->onDelete('cascade');
            $table->foreign('Indisponibilidad_id')->references('idIndisponibilidad')->on('indisponibilidad')->onDelete('cascade');
            $table->unique(['Cita_idCita', 'Indisponibilidad_id']);
        });

        Schema::create('notificacion_web', function (Blueprint $table) {
            $table->id('idNotificacionWeb');
            $table->unsignedBigInteger('Usuario_idUsuario');
            $table->unsignedBigInteger('CitaAfectada_id')->nullable();
            $table->string('titulo', 120);
            $table->text('mensaje');
            $table->boolean('leida')->default(false);
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('Usuario_idUsuario')->references('idUsuario')->on('Usuario')->onDelete('cascade');
            $table->foreign('CitaAfectada_id')->references('idCitaAfectada')->on('cita_afectada')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificacion_web');
        Schema::dropIfExists('cita_afectada');
        Schema::dropIfExists('indisponibilidad');
    }
};

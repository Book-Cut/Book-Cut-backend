<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cita', function (Blueprint $table) {
            $table->id('idCita')->unsigned()->autoIncrement();
            $table->date('Fecha_hora');
            $table->enum('estado', ['Confirmado', 'Pendiente', 'Cancelado']);
            $table->unsignedBigInteger('Valora_Idvalora')->nullable();
            $table->unsignedBigInteger('Servicio_idServicio');
            $table->unsignedBigInteger('Usuario_idUsuarioCli');
            $table->unsignedBigInteger('Usuario_idUsuarioBar');

            $table->foreign('Valora_Idvalora')->references('Idvalora')->on('valora');
            $table->foreign('Servicio_idServicio')->references('idServicio')->on('servicio');
            $table->foreign('Usuario_idUsuarioCli')->references('idUsuario')->on('Usuario');
            $table->foreign('Usuario_idUsuarioBar')->references('idUsuario')->on('Usuario');
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('cita');
    }
};

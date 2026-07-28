<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notificacion', function (Blueprint $table) {
            $table->id('idNotificacion');
            $table->enum('tipo', ['Sms', 'Correo']);
            $table->text('mensaje');
            $table->date('fecha_envio');
            $table->unsignedBigInteger('Cita_idCita');
            $table->foreign('Cita_idCita')->references('idCita')->on('cita');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notificacion');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cita_servicio', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('idCita');
            $table->unsignedBigInteger('idServicio');

            $table->foreign('idCita')->references('idCita')->on('cita')->onDelete('cascade');
            $table->foreign('idServicio')->references('idServicio')->on('servicio')->onDelete('cascade');
            
            // Garantizar que no se duplique exactamente el mismo servicio dentro de la misma cita
            $table->unique(['idCita', 'idServicio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cita_servicio');
    }
};
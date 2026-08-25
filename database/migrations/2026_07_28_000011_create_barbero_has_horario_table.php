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
        Schema::create('barbero_has_horario', function (Blueprint $table) {
            $table->id('Idbarbero_has_horario');
            $table->unsignedBigInteger('horario_idhorario');
            $table->unsignedBigInteger('Usuario_idUsuarioBar');
            $table->foreign('horario_idhorario')->references('idhorario')->on('horario');
            $table->foreign('Usuario_idUsuarioBar')->references('idUsuario')->on('Usuario');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barbero_has_horario');
    }
};

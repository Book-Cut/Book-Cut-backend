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
        Schema::create('barbero_servicio_table', function (Blueprint $table) {
            $table->unsignedBigInteger('Usuario_idUsuarioBar');
            $table->unsignedBigInteger('idServicio');

            $table->foreign('Usuario_idUsuarioBar')->references('idUsuario')->on('usuario');
            $table->foreign('idServicio')->references('idServicio')->on('servicio');

            $table->primary(['Usuario_idUsuarioBar', 'idServicio']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barbero_servicio_table');
    }
};

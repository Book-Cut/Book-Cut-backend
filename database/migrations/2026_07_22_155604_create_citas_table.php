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
        Schema::create('citas', function (Blueprint $table) {
            $table->id('idCita');
            $table->timestamps();
            $table->text('estado');
            $table->integer('Usuario_idUsuarioCli');
            $table->integer('Usuario_idUsuarioBar');
            $table->integer('estado')->default(1);
            $table->integer('Valora_Idvalora');
            $table->integer('Servicio_idServicio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('citas');
    }
};


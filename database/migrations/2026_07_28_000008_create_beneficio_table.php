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
        Schema::create('beneficio', function (Blueprint $table) {
            $table->id("ID_publicacion")->unsigned()->autoIncrement();
            $table->string('titulo', 100);
            $table->string('Tipo_beneficio', 50)->nullable();
            $table->date('Fecha_inicio')->nullable();
            $table->date('Fecha_fin')->nullable();
            $table->unsignedBigInteger('Usuario_idUsuario');

            $table->foreign('Usuario_idUsuario')->references('idUsuario')->on('Usuario');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('beneficio');
    }
};

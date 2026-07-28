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
        Schema::create('valora', function (Blueprint $table) {
            $table->id('Idvalora')->unsigned()->autoIncrement();
            $table->integer('Evaluacion_(Barbero)_idEvaluacion_Bar')->unsigned();
            $table->integer('Evaluacion_(Cliente)_idEvaluacion_cli')->unsigned();
            $table->string('Fecha_evaluacion_bar', 45);
            $table->string('Fecha_evaluacion_cli', 45);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('valora');
    }
};

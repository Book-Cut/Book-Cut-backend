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
        Schema::create('valora', function (Blueprint $table) {
            $table->id('Idvalora')->unsigned()->autoIncrement();
            $table->integer('EvaluacionBar')->unsigned();
            $table->integer('EvaluacionCita')->unsigned();
            $table->dateTime('Fecha_evaluacion_bar')->useCurrent();
            $table->dateTime('Fecha_evaluacion_cita');
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

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
        if (!Schema::hasTable('Usuario')) {
            Schema::create('Usuario', function (Blueprint $table) {
                $table->id('idUsuario');
                $table->text('Nombre');
                $table->string('correo', 255)->unique();
                $table->string('telefono', 45);
                $table->string('contrasenha', 45);
                $table->string('especialidad', 45);
                $table->text('disponibilidad');
                $table->string('horario', 45);
                $table->unsignedBigInteger('Roles_IDRol');

                $table->primary('idUsuario');
                $table->foreign('Roles_IDRol')->references('iDRol')->on('roles');
            });
        }
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('Usuario');
    }
};

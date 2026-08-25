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
                $table->string('foto_perfil', 255)->nullable();
                $table->text('Nombre');
                $table->string('correo', 255)->unique();
                $table->string('telefono', 45);
                $table->string('contrasenha', 255);
                $table->string('especialidad', 45)->nullable()->default("null");
                $table->text('disponibilidad')->nullable()->default("null");
                $table->string('horario', 45)->nullable()->default("null");
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

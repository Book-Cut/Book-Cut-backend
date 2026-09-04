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
        if (!Schema::hasTable('perfil')) {
            Schema::create('perfil', function (Blueprint $table) {
                $table->id('idPerfil');
                $table->integer('Ranking')->default(0);
                $table->string('foto_perfil', 255)->nullable();
                $table->foreignId('Usuario_idUsuario')->references('idUsuario')->on('Usuario');
            });
        }
    }



    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('perfil');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cita', function (Blueprint $table) {
            $table->string('nombre_cliente')->nullable();
            $table->string('telefono_cliente', 45)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('cita', function (Blueprint $table) {
            $table->dropColumn(['nombre_cliente', 'telefono_cliente']);
        });
    }
};

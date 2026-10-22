<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cita', function (Blueprint $table) {
            $table->enum('metodo_pago', ['Efectivo', 'Nequi', 'Tarjeta', 'Transferencia'])->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('cita', function (Blueprint $table) {
            $table->dropColumn('metodo_pago');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
  public function up(): void
    {
        Schema::create('factura', function (Blueprint $table) {
            $table->id('idfactura');
            $table->string('numero_factura', 20)->unique();
            $table->dateTime('fecha_emision')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->decimal('subtotal', 12, 2);
            $table->decimal('total_pagar', 12, 2);
            $table->enum('metodo_pago', ['Efectivo', 'Tarjeta', 'Transferencia', 'Nequi'])->default('Efectivo');
            $table->unsignedBigInteger('Cita_idCita');
            $table->unsignedBigInteger('Usuario_idUsuario');

            $table->foreign('Cita_idCita')->references('idCita')->on('cita');
            $table->foreign('Usuario_idUsuario')->references('idUsuario')->on('usuario');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facturas');
    }
};

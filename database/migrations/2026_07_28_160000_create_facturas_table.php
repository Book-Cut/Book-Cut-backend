<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('factura', function (Blueprint $table) {
            $table->id('idfactura');
            $table->string('numero_factura')->unique();
            $table->dateTime('fecha_emision');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('total_pagar', 12, 2);
            $table->enum('metodo_pago', ['Efectivo', 'Nequi', 'Tarjeta', 'Transferencia']);
            $table->unsignedBigInteger('Cita_idCita');
            $table->unsignedBigInteger('Usuario_idUsuario');

            $table->foreign('Cita_idCita')->references('idCita')->on('cita')->onDelete('cascade');
            $table->foreign('Usuario_idUsuario')->references('idUsuario')->on('Usuario')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('factura');
    }
};
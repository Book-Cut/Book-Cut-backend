<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cita_factura', function (Blueprint $table) {
            $table->unsignedBigInteger('idfactura');
            $table->unsignedBigInteger('idCita')->unique();
            $table->foreign('idfactura')->references('idfactura')->on('factura')->onDelete('cascade');
            $table->foreign('idCita')->references('idCita')->on('cita')->onDelete('cascade');
        });

        DB::table('factura')
            ->select('idfactura', 'Cita_idCita')
            ->orderBy('idfactura')
            ->chunk(500, function ($facturas) {
                DB::table('cita_factura')->insert(
                    $facturas->map(fn ($factura) => [
                        'idfactura' => $factura->idfactura,
                        'idCita' => $factura->Cita_idCita,
                    ])->all()
                );
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('cita_factura');
    }
};

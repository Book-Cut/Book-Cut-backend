<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $citasDuplicadas = DB::table('factura')
            ->whereIn('estado_factura', ['Pagada', 'Anulada'])
            ->whereNotNull('metodo_pago')
            ->select('Cita_idCita')
            ->groupBy('Cita_idCita')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($citasDuplicadas) {
            throw new RuntimeException('No se puede limitar una factura por cita: hay citas con facturas duplicadas.');
        }

        $facturasPagadasSinMetodo = DB::table('factura')
            ->where('estado_factura', 'Pagada')
            ->where(function ($consulta) {
                $consulta->whereNull('metodo_pago')
                    ->orWhere('metodo_pago', 'Pendiente_Pago');
            })
            ->exists();

        if ($facturasPagadasSinMetodo) {
            throw new RuntimeException('Hay facturas pagadas sin método de pago. Corrige esos registros antes de migrar.');
        }

        Schema::table('cita', function (Blueprint $table) {
            $table->enum('estado_pago', ['Pendiente', 'Pagado'])->default('Pendiente');
            $table->enum('modalidad_pago', ['En línea', 'Presencial'])->default('Presencial');
        });

        Schema::table('factura', function (Blueprint $table) {
            $table->enum('metodo_pago', ['Efectivo', 'Nequi', 'Tarjeta', 'Transferencia', 'Pendiente_Pago'])
                ->nullable()
                ->change();
            $table->enum('estado_factura', ['Emitida', 'Pendiente de pago', 'Pagada', 'Anulada'])
                ->default('Pendiente de pago')
                ->change();
        });

        DB::table('factura')
            ->whereNull('metodo_pago')
            ->orWhere('metodo_pago', 'Pendiente_Pago')
            ->orWhereIn('estado_factura', ['Emitida', 'Pendiente de pago'])
            ->delete();

        $citasPagadas = DB::table('factura')
            ->whereIn('estado_factura', ['Pagada', 'Anulada'])
            ->pluck('Cita_idCita');

        if ($citasPagadas->isNotEmpty()) {
            DB::table('cita')
                ->whereIn('idCita', $citasPagadas)
                ->update(['estado_pago' => 'Pagado']);
        }

        Schema::table('factura', function (Blueprint $table) {
            $table->enum('metodo_pago', ['Efectivo', 'Nequi', 'Tarjeta', 'Transferencia'])
                ->change();
            $table->enum('estado_factura', ['Pagada', 'Anulada'])
                ->default('Pagada')
                ->change();
            $table->unique('Cita_idCita');
        });
    }

    public function down(): void
    {
        Schema::table('factura', function (Blueprint $table) {
            $table->dropUnique(['Cita_idCita']);
            $table->enum('metodo_pago', ['Efectivo', 'Nequi', 'Tarjeta', 'Transferencia', 'Pendiente_Pago'])
                ->nullable()
                ->change();
            $table->enum('estado_factura', ['Emitida', 'Pendiente de pago', 'Pagada', 'Anulada'])
                ->default('Pendiente de pago')
                ->change();
        });

        Schema::table('cita', function (Blueprint $table) {
            $table->dropColumn(['estado_pago', 'modalidad_pago']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('valora', function (Blueprint $table) {
            if (!Schema::hasColumn('valora', 'Comentario')) {
                $table->text('Comentario')->nullable()->after('Fecha_evaluacion_cita');
            }
        });
    }

    public function down(): void
    {
        Schema::table('valora', function (Blueprint $table) {
            if (Schema::hasColumn('valora', 'Comentario')) {
                $table->dropColumn('Comentario');
            }
        });
    }
};

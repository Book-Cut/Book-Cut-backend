<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('Usuario', function (Blueprint $table): void {
            $table->string('telefono', 45)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('Usuario', function (Blueprint $table): void {
            $table->string('telefono', 45)->nullable(false)->change();
        });
    }
};

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
        Schema::table('transferencias_externas', function (Blueprint $table) {
            $table->string('codigo_confirmacion')->nullable()->change();
            $table->datetime('fecha_confirmacion')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transferencias_externas', function (Blueprint $table) {
            $table->string('codigo_confirmacion')->nullable(false)->change();
            $table->datetime('fecha_confirmacion')->nullable(false)->change();
        });
    }
};

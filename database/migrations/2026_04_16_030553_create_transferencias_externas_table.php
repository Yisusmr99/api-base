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
        Schema::create('transferencias_externas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_transaccion')->constrained('transacciones')->cascadeOnDelete();
            $table->string('banco_externo');
            $table->string('cuenta_externa');
            $table->string('codigo_confirmacion')->unique();
            $table->string('tipo', 20);
            $table->string('estado', 20)->default('confirmada');
            $table->datetime('fecha_envio');
            $table->datetime('fecha_confirmacion');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transferencias_externas');
    }
};

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
        Schema::create('transacciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_cuenta_origen')->nullable();
            $table->unsignedBigInteger('id_cuenta_destino')->nullable();
            $table->decimal('monto', 15, 2);
            $table->string('referencia')->nullable();
            $table->string('tipo_transaccion', 20);
            $table->string('estado', 20)->default('pendiente');
            $table->boolean('es_externa')->default(false);
            $table->string('banco_externo')->nullable();
            $table->datetime('fecha_transaccion')->nullable();
            $table->datetime('hora_transaccion')->nullable();
            $table->timestamps();

            $table->foreign('id_cuenta_origen')->references('id')->on('cuentas')->nullOnDelete();
            $table->foreign('id_cuenta_destino')->references('id')->on('cuentas')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transacciones');
    }
};

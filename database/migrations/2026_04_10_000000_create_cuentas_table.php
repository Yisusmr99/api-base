<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuentas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_cliente')->constrained('clientes')->cascadeOnDelete();
            $table->string('numero_cuenta')->unique();
            $table->decimal('saldo', 15, 2)->default(0);
            $table->decimal('saldo_disponible', 15, 2)->default(0);
            $table->string('tipo_cuenta', 20);
            $table->timestamp('fecha_apertura')->nullable();
            $table->timestamp('fecha_cierre')->nullable();
            $table->string('moneda', 3)->default('Q');
            $table->boolean('estado')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuentas');
    }
};



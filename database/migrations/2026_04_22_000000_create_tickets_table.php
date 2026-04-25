<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Tickets', function (Blueprint $table) {
            $table->id('id_ticket');
            $table->string('codigo_ticket')->unique();
            $table->foreignId('id_cliente')->constrained('clientes')->cascadeOnDelete();
            $table->enum('id_tipo_ticket', ['consulta', 'reclamo', 'solicitud', 'soporte']);
            $table->enum('id_estado_ticket', ['abierto', 'asignado', 'en proceso', 'pendiente', 'resuelto', 'cerrado'])->default('abierto');
            $table->unsignedBigInteger('id_prioridad')->nullable();
            $table->string('asunto');
            $table->text('descripcion')->nullable();
            $table->timestamp('fecha_creacion')->useCurrent();
            $table->timestamp('fecha_cierre')->nullable();
            $table->enum('canal_origen', ['telefono', 'correo', 'presencial', 'web'])->nullable();
            $table->foreignId('creado_por')->constrained('users')->cascadeOnDelete();
            $table->text('observaciones_generales')->nullable();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Tickets');
    }
};

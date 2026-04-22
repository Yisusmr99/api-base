<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historial_ticket', function (Blueprint $table) {
            $table->id('id_historial');
            $table->unsignedBigInteger('id_ticket');
            $table->foreign('id_ticket')->references('id_ticket')->on('Tickets')->cascadeOnDelete();
            $table->foreignId('id_usuario')->constrained('users')->cascadeOnDelete();
            $table->timestamp('fecha_movimiento')->useCurrent();
            $table->enum('tipo_movimiento', ['creacion', 'cambio_estado', 'reasignacion', 'comentario', 'cierre']);
            $table->text('descripcion')->nullable();
            $table->enum('estado_anterior', ['abierto', 'asignado', 'en proceso', 'pendiente', 'resuelto', 'cerrado'])->nullable();
            $table->enum('estado_nuevo', ['abierto', 'asignado', 'en proceso', 'pendiente', 'resuelto', 'cerrado'])->nullable();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_ticket');
    }
};

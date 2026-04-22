<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asignaciones_ticket', function (Blueprint $table) {
            $table->id('id_asignacion');
            $table->unsignedBigInteger('id_ticket');
            $table->foreign('id_ticket')->references('id_ticket')->on('Tickets')->cascadeOnDelete();
            $table->foreignId('id_usuario_asignado')->constrained('users')->cascadeOnDelete();
            $table->foreignId('id_usuario_asigna')->constrained('users')->cascadeOnDelete();
            $table->timestamp('fecha_asignacion')->useCurrent();
            $table->text('motivo_asignacion')->nullable();
            $table->enum('estado_asignacion', ['activa', 'reasignada', 'finalizada'])->default('activa');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asignaciones_ticket');
    }
};

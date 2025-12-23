<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pedido_liquidaciones', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('pedido_id')->unique();
            $table->unsignedBigInteger('clinica_id');
            $table->unsignedBigInteger('paciente_id');

            $table->enum('estado', ['borrador', 'confirmada'])->default('confirmada');
            $table->unsignedBigInteger('total_gs')->default(0);

            $table->unsignedBigInteger('liquidado_por')->nullable();
            $table->timestamp('liquidado_at')->nullable();

            // Reservado para el paso 2 (quién paga), por ahora queda null
            $table->enum('pagador_tipo', ['clinica', 'paciente'])->nullable();
            $table->unsignedBigInteger('pagador_id')->nullable();

            $table->timestamps();

            $table->foreign('pedido_id')->references('id')->on('pedidos')->onDelete('cascade');
            $table->foreign('clinica_id')->references('id')->on('clinicas')->onDelete('restrict');
            $table->foreign('paciente_id')->references('id')->on('pacientes')->onDelete('restrict');
            $table->foreign('liquidado_por')->references('id')->on('users')->nullOnDelete();

            $table->index(['clinica_id', 'estado']);
            $table->index(['paciente_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedido_liquidaciones');
    }
};

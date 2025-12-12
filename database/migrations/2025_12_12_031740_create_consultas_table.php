<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultas', function (Blueprint $table) {
            $table->id();

            // Multi-tenant
            $table->foreignId('clinica_id')
                ->constrained('clinicas')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('paciente_id')
                ->constrained('pacientes')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Profesional / usuario que registra la consulta
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->dateTime('fecha_hora');

            $table->string('motivo_consulta', 255);
            $table->text('descripcion_problema')->nullable();

            $table->text('antecedentes_medicos')->nullable();
            $table->text('antecedentes_odontologicos')->nullable();
            $table->text('medicamentos_actuales')->nullable();
            $table->text('alergias')->nullable();

            $table->text('diagnostico_presuntivo')->nullable();
            $table->text('plan_tratamiento')->nullable();
            $table->text('observaciones')->nullable();

            $table->timestamps();

            $table->index(['clinica_id', 'paciente_id']);
            $table->index(['fecha_hora']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultas');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pacientes', function (Blueprint $table) {
            $table->id();

            // Multi-tenant: paciente pertenece a una clínica
            $table->foreignId('clinica_id')
                ->constrained('clinicas')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('nombre', 120);
            $table->string('apellido', 120)->nullable();
            $table->string('documento', 30)->nullable(); // CI / pasaporte
            $table->date('fecha_nacimiento')->nullable();

            // M = masculino, F = femenino, O = otro
            $table->string('genero', 1)->nullable();

            $table->string('telefono', 50)->nullable();
            $table->string('email')->nullable();
            $table->string('direccion', 255)->nullable();
            $table->string('ciudad', 100)->nullable();

            $table->boolean('is_active')->default(true);
            $table->text('observaciones')->nullable();

            $table->timestamps();

            $table->index(['clinica_id', 'documento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pacientes');
    }
};

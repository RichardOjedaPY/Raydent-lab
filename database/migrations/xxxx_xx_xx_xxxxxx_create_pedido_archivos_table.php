<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pedido_archivos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();

            // Denormalización para performance multi-tenant (evita joins en listados)
            $table->foreignId('clinica_id')->constrained('clinicas')->cascadeOnUpdate();
            $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnUpdate();

            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();

            // “grupo” te permite clasificar: resultados, dicom, raw, pdf, etc.
            $table->string('grupo', 30)->default('resultado');

            $table->string('original_name', 255);
            $table->string('ext', 20)->nullable();
            $table->string('mime', 120)->nullable();
            $table->unsignedBigInteger('size')->default(0);

            // Privado por defecto (recomendado por datos sensibles)
            $table->string('disk', 30)->default('private');
            $table->string('path', 700);

            $table->string('checksum', 64)->nullable(); // sha256 opcional
            $table->timestamps();

            $table->index(['pedido_id', 'grupo']);
            $table->index(['clinica_id', 'paciente_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedido_archivos');
    }
};

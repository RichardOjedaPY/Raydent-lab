<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pedido_fotos_realizadas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            $table->foreignId('clinica_id')->constrained('clinicas')->cascadeOnUpdate();
            $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnUpdate();

            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();

            // Slot fijo: fotografia_frontal, oclusal_superior, etc.
            $table->string('slot', 50);

            $table->string('original_name', 255);
            $table->string('ext', 20)->nullable();
            $table->string('mime', 120)->nullable();
            $table->unsignedBigInteger('size')->default(0);

            $table->string('disk', 30)->default('private');
            $table->string('path', 700);

            $table->timestamps();

            $table->unique(['pedido_id', 'slot']);
            $table->index(['clinica_id', 'paciente_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedido_fotos_realizadas');
    }
};

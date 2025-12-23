<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tarifario_conceptos', function (Blueprint $table) {
            $table->id();
            $table->string('concept_key', 80)->unique(); // ej: rx_panoramica_convencional, foto:frente, cefa:usp, doc:doc_simplificada_1
            $table->string('nombre', 255);
            $table->string('grupo', 60)->nullable();     // RX / CT / Fotos / Cefalometrías / Documentación / Piezas / Entrega
            $table->unsignedBigInteger('precio_gs')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['grupo', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tarifario_conceptos');
    }
};

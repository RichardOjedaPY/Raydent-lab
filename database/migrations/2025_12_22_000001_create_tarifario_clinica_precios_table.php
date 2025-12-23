<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tarifario_clinica_precios', function (Blueprint $table) {
            $table->id();

            $table->foreignId('clinica_id')
                ->constrained('clinicas')
                ->cascadeOnDelete();

            $table->string('concept_key', 80);
            $table->unsignedBigInteger('precio_gs')->default(0);

            $table->timestamps();

            $table->unique(['clinica_id', 'concept_key']);
            $table->index('concept_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tarifario_clinica_precios');
    }
};

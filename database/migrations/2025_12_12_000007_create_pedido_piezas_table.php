<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedido_piezas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pedido_id')
                ->constrained('pedidos')
                ->cascadeOnDelete();

            // Código FDI / internacional de la pieza: 11, 22, 37, 85, etc.
            $table->string('pieza_codigo', 4);

            // Tipo de uso (por ahora periapical, a futuro podríamos reutilizar para otra cosa)
            $table->string('tipo', 20)->default('periapical');

            // Opcional para tu 3D o para filtros (adulto/niño, arco, cuadrante, etc.)
            $table->string('arcada', 20)->nullable();   // ej: adulto_sup, adulto_inf, infantil_sup...
            $table->unsignedTinyInteger('cuadrante')->nullable(); // 1..4 o 5..8

            $table->timestamps();

            $table->unique(['pedido_id', 'pieza_codigo', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedido_piezas');
    }
};

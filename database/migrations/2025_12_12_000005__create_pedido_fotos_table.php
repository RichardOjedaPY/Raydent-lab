<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedido_fotos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pedido_id')
                ->constrained('pedidos')
                ->cascadeOnDelete();

            // tipo: uno de
            // frente, perfil_derecho, perfil_izquierdo, sonriendo, tercio_inferior_face,
            // frontal_oclusion, lateral_derecha, lateral_izquierda, oclusal_superior, oclusal_inferior
            $table->string('tipo', 40);

            $table->timestamps();

            $table->unique(['pedido_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedido_fotos');
    }
};

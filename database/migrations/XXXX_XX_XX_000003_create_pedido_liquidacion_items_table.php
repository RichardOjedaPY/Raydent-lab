<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pedido_liquidacion_items', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('liquidacion_id');

            $table->string('concept_key', 80);   // clave estable
            $table->string('concepto', 255);     // snapshot del label
            $table->string('grupo', 60)->nullable();

            $table->unsignedInteger('cantidad')->default(1);
            $table->string('observacion', 255)->nullable();

            $table->unsignedBigInteger('precio_base_gs')->default(0);
            $table->unsignedBigInteger('precio_final_gs')->default(0);
            $table->unsignedBigInteger('subtotal_gs')->default(0);

            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();

            $table->foreign('liquidacion_id')->references('id')->on('pedido_liquidaciones')->onDelete('cascade');

            $table->unique(['liquidacion_id', 'concept_key']);
            $table->index(['grupo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedido_liquidacion_items');
    }
};

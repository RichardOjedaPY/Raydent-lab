<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedido_cefalometrias', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pedido_id')
                ->constrained('pedidos')
                ->cascadeOnDelete();

            // tipo: usp, unicamp, usp_unicamp, tweed, steiner, homem_neto,
            // downs, mcnamara, bimler, jarabak, profis,
            // ricketts, ricketts_frontal, petrovic, sassouni, schwarz,
            // trevisi, valieri, rocabado, adenoides
            $table->string('tipo', 40);

            $table->timestamps();

            $table->unique(['pedido_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedido_cefalometrias');
    }
};

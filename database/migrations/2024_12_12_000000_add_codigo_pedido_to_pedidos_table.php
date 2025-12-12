<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            // Agregamos el campo después de 'codigo' (ajusta si quieres otro orden)
            $table->string('codigo_pedido', 20)
                ->nullable()
                ->unique()
                ->after('codigo');
        });
    }

    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropColumn('codigo_pedido');
        });
    }
};

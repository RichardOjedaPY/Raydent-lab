<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pedido_liquidaciones', function (Blueprint $table) {
            // Monto acumulado pagado (para poder calcular saldo rápidamente)
            $table->unsignedBigInteger('pagado_gs')->default(0)->after('total_gs');
        });
    }

    public function down(): void
    {
        Schema::table('pedido_liquidaciones', function (Blueprint $table) {
            $table->dropColumn('pagado_gs');
        });
    }
};

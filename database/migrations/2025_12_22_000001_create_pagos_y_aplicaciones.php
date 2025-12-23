<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ✅ En tu DB ya existe 'pagos'. Esto evita el error.
        if (!Schema::hasTable('pagos')) {
            Schema::create('pagos', function (Blueprint $table) {
                $table->id();

                $table->foreignId('clinica_id')->constrained('clinicas');
                $table->date('fecha');
                $table->string('metodo', 30)->default('efectivo');
                $table->unsignedBigInteger('monto_gs');
                $table->string('referencia', 120)->nullable();
                $table->text('observacion')->nullable();
                $table->foreignId('user_id')->constrained('users');

                // Fase 2 (Caja): dejamos el campo, pero SIN FK todavía (porque caja_sesiones aún no existe)
                $table->unsignedBigInteger('caja_sesion_id')->nullable();

                $table->timestamps();
            });
        }

        // ✅ Esto es lo nuevo que falta: aplicaciones del pago a liquidaciones
        if (!Schema::hasTable('pago_aplicaciones')) {
            Schema::create('pago_aplicaciones', function (Blueprint $table) {
                $table->id();

                $table->foreignId('pago_id')
                    ->constrained('pagos')
                    ->cascadeOnDelete();

                // 👇 OJO: en tu proyecto la tabla es pedido_liquidaciones
                $table->foreignId('liquidacion_id')
                    ->constrained('pedido_liquidaciones')
                    ->cascadeOnDelete();

                $table->unsignedBigInteger('monto_gs');

                $table->timestamps();

                $table->unique(['pago_id', 'liquidacion_id']);
                $table->index(['liquidacion_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pago_aplicaciones');

        // ⚠️ NO dropeamos 'pagos' en rollback porque puede ser una tabla ya existente del sistema.
        // Si algún día querés que esta migración también la elimine en rollback,
        // se hace con una estrategia distinta para diferenciar "preexistente" vs "creada".
    }
};

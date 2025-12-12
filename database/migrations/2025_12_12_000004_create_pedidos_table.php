<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();

            // ─────────── Multi-tenant / relaciones principales ───────────
            $table->foreignId('clinica_id')
                ->constrained('clinicas')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('paciente_id')
                ->constrained('pacientes')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Consulta desde la que se generó el pedido (opcional)
            $table->foreignId('consulta_id')
                ->nullable()
                ->constrained('consultas')
                ->nullOnDelete();

            // Usuario que crea el pedido (puede ser clínica, admin, técnico)
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Técnico asignado en el laboratorio
            $table->foreignId('tecnico_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Código interno tipo RAY-2025-000123
            $table->string('codigo', 40)->unique();

            // Estado y prioridad de trabajo
            $table->string('estado', 20)->default('pendiente'); // pendiente, en_proceso, completado, cancelado
            $table->string('prioridad', 20)->default('normal'); // normal, urgente

            // Fechas importantes
            $table->date('fecha_solicitud')->nullable();        // fecha en que la clínica cargó
            $table->date('fecha_agendada')->nullable();         // "AGENDADO PARA"
            $table->time('hora_agendada')->nullable();          // "HORA:"
            $table->timestamp('fecha_inicio_trabajo')->nullable();
            $table->timestamp('fecha_fin_trabajo')->nullable();

            // ─────────── Cabecera página 2: datos de contacto ───────────
            $table->string('doctor_nombre', 120)->nullable();    // Dr(a)
            $table->string('doctor_telefono', 50)->nullable();
            $table->string('doctor_email', 120)->nullable();
            $table->string('paciente_documento', 50)->nullable(); // copia rápida del CI (aunque ya esté en Paciente)
            $table->string('direccion', 255)->nullable();        // dirección de la clínica o envío

            // ─────────── EXÁMENES RADIOGRÁFICOS ───────────
            // PANORÁMICA
            $table->boolean('rx_panoramica_convencional')->default(false);
            $table->boolean('rx_panoramica_trazado_implante')->default(false);
            $table->string('rx_panoramica_trazado_region', 120)->nullable();
            $table->boolean('rx_panoramica_atm_boca_abierta_cerrada')->default(false);

            // TELERRADIOGRAFÍA
            $table->boolean('rx_teleradiografia_lateral')->default(false);
            $table->boolean('rx_teleradiografia_frontal_pa')->default(false);
            $table->boolean('rx_teleradiografia_waters')->default(false);
            $table->boolean('rx_teleradiografia_indice_carpal_edad_osea')->default(false);

            // INTERPROXIMAL
            $table->boolean('rx_interproximal_premolares_derecho')->default(false);
            $table->boolean('rx_interproximal_premolares_izquierdo')->default(false);
            $table->boolean('rx_interproximal_molares_derecho')->default(false);
            $table->boolean('rx_interproximal_molares_izquierdo')->default(false);

            // PERIAPICAL
            $table->boolean('rx_periapical_dientes_senalados')->default(false);
            $table->boolean('rx_periapical_status_radiografico')->default(false);
            $table->boolean('rx_periapical_tecnica_clark')->default(false);
            $table->string('rx_periapical_region', 150)->nullable();

            // CON INFORME / SIN INFORME (radio → lo modelamos como booleano)
            $table->boolean('rx_con_informe')->default(true); // true = CON INFORME; false = SIN INFORME

            // ─────────── ESCANEAMIENTO INTRAORAL ───────────
            $table->boolean('intraoral_maxilar_superior')->default(false);
            $table->boolean('intraoral_mandibula')->default(false);
            $table->boolean('intraoral_maxilar_mandibula_completa')->default(false);

            // Modelo
            $table->boolean('intraoral_modelo_con_base')->default(false); // Estudio
            $table->boolean('intraoral_modelo_sin_base')->default(false); // Trabajo

            // ─────────── TOMOGRAFÍA COMPUTADORIZADA ───────────
            $table->boolean('ct_maxilar_completa')->default(false);
            $table->boolean('ct_mandibula_completa')->default(false);
            $table->boolean('ct_maxilar_arco_cigomatico')->default(false);
            $table->boolean('ct_atm')->default(false);
            $table->boolean('ct_parcial')->default(false);
            $table->string('ct_parcial_zona', 150)->nullable();
            $table->boolean('ct_region_senalada_abajo')->default(false);

            // Formas de entrega
            $table->boolean('entrega_pdf')->default(false);              // Digital (PDF)
            $table->boolean('entrega_papel_fotografico')->default(false);
            $table->boolean('entrega_dicom')->default(false);
            $table->boolean('entrega_software_visualizacion')->default(false);
            $table->string('entrega_software_detalle', 150)->nullable(); // nombre del software si lo especifican

            // ─────────── DOCUMENTACIÓN (paquetes) ───────────
            // En el talonario son radios → elegimos UN solo tipo o ninguno
            $table->enum('documentacion_tipo', [
                'doc_simplificada_1',
                'doc_simplificada_2',
                'doc_completa_digital',
                'doc_completa_fotos_modelo',
            ])->nullable();

            // ─────────── FINALIDAD DEL EXAMEN ───────────
            $table->boolean('finalidad_implantes')->default(false);
            $table->boolean('finalidad_dientes_incluidos')->default(false);
            $table->boolean('finalidad_terceros_molares')->default(false);
            $table->boolean('finalidad_supernumerarios')->default(false);
            $table->boolean('finalidad_perforacion_radicular')->default(false);
            $table->boolean('finalidad_sospecha_fractura')->default(false);
            $table->boolean('finalidad_patologia')->default(false);

            // ─────────── DESCRIBIR EL CASO ───────────
            $table->text('descripcion_caso')->nullable();

            $table->timestamps();

            $table->index(['clinica_id', 'paciente_id']);
            $table->index(['codigo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};

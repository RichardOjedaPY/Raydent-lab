<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Models\TarifarioConcepto;
use App\Models\PedidoLiquidacion;
use App\Models\PedidoLiquidacionItem;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\TarifarioClinicaPrecio;

class PedidoLiquidacionController extends Controller
{
    public function __construct()
    {
        /**
         * ✅ Este flujo NO es "editar pedido".
         * Es "liquidar / cargar precios", con permiso separado.
         */
        $this->middleware('permission:pedidos.liquidar')->only(['edit', 'update']);
    }

    public function edit(Pedido $pedido)
    {
        $user = auth()->user();

        if (! $user || ! $user->can('pedidos.liquidar')) {
            abort(403);
        }

        $pedido->load(['clinica', 'paciente', 'fotos', 'cefalometrias', 'piezas', 'liquidacion.items']);

        [$fotosTipos, $cefalometriasTipos, $documentaciones] = $this->getCatalogos();

        // 1) Construir “conceptos seleccionados” desde el pedido
        $items = $this->buildItemsFromPedido($pedido, $fotosTipos, $cefalometriasTipos, $documentaciones);

        // 2) Asegurar tarifario base (crea registros si faltan, con precio 0)
        foreach ($items as $it) {
            TarifarioConcepto::firstOrCreate(
                ['concept_key' => $it['key']],
                [
                    'nombre'    => $it['label'],
                    'grupo'     => $it['grupo'] ?? null,
                    'precio_gs' => 0,
                    'is_active' => true,
                ]
            );
        }

        // 3) Precargar precios base desde tarifario
        $tarifas = TarifarioConcepto::whereIn('concept_key', collect($items)->pluck('key')->all())
            ->get()
            ->keyBy('concept_key');

        $overrides = TarifarioClinicaPrecio::where('clinica_id', $pedido->clinica_id)
            ->whereIn('concept_key', collect($items)->pluck('key')->all())
            ->get()
            ->keyBy('concept_key');

        // 4) Si ya existe liquidación, precargar valores (precio_final / observacion)
        $liq = $pedido->liquidacion;

        $itemsByKey = [];
        if ($liq) {
            foreach ($liq->items as $row) {
                $itemsByKey[$row->concept_key] = $row;
            }
        }

        $viewItems = [];
        $orden = 0;

        foreach ($items as $it) {
            $key = $it['key'];

            $ov = $overrides->get($key);
            $base = $ov ? (int) $ov->precio_gs : (int) optional($tarifas->get($key))->precio_gs;

            $existing = $itemsByKey[$key] ?? null;
            $precioFinal = $existing ? (int) $existing->precio_final_gs : $base;

            $viewItems[] = [
                'key'             => $key,
                'label'           => $it['label'],
                'grupo'           => $it['grupo'] ?? null,
                'cantidad'        => (int) ($it['cantidad'] ?? 1),
                'observacion'     => $existing ? ($existing->observacion ?? '') : ($it['observacion'] ?? ''),
                'precio_base_gs'  => $base,
                'precio_final_gs' => $precioFinal,
                'orden'           => $orden++,
            ];
        }

        // 🧾 AUDIT: abrió/vio pantalla de liquidación del pedido (solo registro, no cambia lógica)
        Audit::log('pedidos', 'liquidacion_view', 'Vio pantalla de liquidación', $pedido, [
            'pedido_id'       => (int) $pedido->id,
            'clinica_id'      => (int) ($pedido->clinica_id ?? 0),
            'paciente_id'     => (int) ($pedido->paciente_id ?? 0),
            'liquidacion_id'  => (int) ($liq?->id ?? 0),
            'items_count'     => (int) count($viewItems),
        ]);

        return view('admin.pedidos.liquidar', compact('pedido', 'viewItems', 'liq'));
    }

    public function update(Request $request, Pedido $pedido)
    {
        $user = auth()->user();

        if (! $user || ! $user->can('pedidos.liquidar')) {
            abort(403);
        }

        $pedido->load(['clinica', 'paciente', 'liquidacion.items']);

        $data = $request->validate([
            'items'                   => ['required', 'array', 'min:1'],
            'items.*.key'             => ['required', 'string', 'max:80'],
            'items.*.label'           => ['required', 'string', 'max:255'],
            'items.*.grupo'           => ['nullable', 'string', 'max:60'],
            'items.*.cantidad'        => ['required', 'integer', 'min:1'],
            'items.*.observacion'     => ['nullable', 'string', 'max:255'],
            'items.*.precio_base_gs'  => ['nullable'],
            'items.*.precio_final_gs' => ['required'],
            'items.*.orden'           => ['nullable', 'integer', 'min:0'],
        ]);

        // Sanitizar montos
        $rows = [];
        $total = 0;

        foreach ($data['items'] as $row) {
            $precioBase  = $this->parseGs($row['precio_base_gs'] ?? 0);
            $precioFinal = $this->parseGs($row['precio_final_gs'] ?? 0);

            $cantidad  = (int) $row['cantidad'];
            $subtotal  = (int) ($precioFinal * $cantidad);

            $total += $subtotal;

            $rows[] = [
                'concept_key'      => $row['key'],
                'concepto'         => $row['label'],
                'grupo'            => $row['grupo'] ?? null,
                'cantidad'         => $cantidad,
                'observacion'      => $row['observacion'] ?? null,
                'precio_base_gs'   => $precioBase,
                'precio_final_gs'  => $precioFinal,
                'subtotal_gs'      => $subtotal,
                'orden'            => (int) ($row['orden'] ?? 0),
            ];
        }

        // Para audit con más contexto (sin alterar lógica)
        $liqId = 0;

        DB::transaction(function () use ($pedido, $rows, $total, &$liqId) {
            $liq = PedidoLiquidacion::updateOrCreate(
                ['pedido_id' => $pedido->id],
                [
                    'clinica_id'    => $pedido->clinica_id,
                    'paciente_id'   => $pedido->paciente_id,
                    'estado'        => 'confirmada',
                    'total_gs'      => $total,
                    'liquidado_por' => Auth::id(),
                    'liquidado_at'  => now(),
                ]
            );

            $liqId = (int) $liq->id;

            // Reemplazar items (simple y robusto)
            $liq->items()->delete();

            foreach ($rows as $r) {
                $liq->items()->create($r);
            }
        });

        // 🧾 AUDIT: guardó liquidación (mantiene tu log, solo amplía metadatos)
        Audit::log('pedidos', 'liquidacion_saved', 'Liquidación guardada', $pedido, [
            'pedido_id'      => (int) $pedido->id,
            'clinica_id'     => (int) ($pedido->clinica_id ?? 0),
            'liquidacion_id' => (int) $liqId,
            'items_count'    => (int) count($rows),
            'total_gs'       => (int) $total,
        ]);

        return redirect()
            ->route('admin.pedidos.show', $pedido)
            ->with('success', 'Liquidación guardada correctamente.');
    }

    private function parseGs($value): int
    {
        $s = (string) $value;
        $digits = preg_replace('/\D+/', '', $s);
        return (int) ($digits ?: 0);
    }

    private function buildItemsFromPedido(Pedido $pedido, array $fotosTipos, array $cefalometriasTipos, array $documentaciones): array
    {
        $items = [];

        // --- RX / CT / Intraoral / Entrega: campos booleanos cobrables ---
        $map = $this->conceptLabelMap();

        foreach ($map as $field => $meta) {
            if (!empty($pedido->{$field})) {
                $obs = $meta['obs'] ? ($meta['obs'])($pedido) : null;

                $items[] = [
                    'key'         => $field,
                    'label'       => $meta['label'],
                    'grupo'       => $meta['grupo'] ?? null,
                    'cantidad'    => 1,
                    'observacion' => $obs,
                ];
            }
        }

        // --- Documentación (radio) ---
        if (!empty($pedido->documentacion_tipo) && isset($documentaciones[$pedido->documentacion_tipo])) {
            $items[] = [
                'key'         => 'doc:' . $pedido->documentacion_tipo,
                'label'       => $documentaciones[$pedido->documentacion_tipo],
                'grupo'       => 'Documentación',
                'cantidad'    => 1,
                'observacion' => null,
            ];
        }

        // --- Fotos seleccionadas (1 línea por foto) ---
        foreach ($pedido->fotos as $f) {
            $tipo = (string) $f->tipo;
            $items[] = [
                'key'         => 'foto:' . $tipo,
                'label'       => 'Foto - ' . ($fotosTipos[$tipo] ?? $tipo),
                'grupo'       => 'Fotos',
                'cantidad'    => 1,
                'observacion' => null,
            ];
        }

        // --- Cefalometrías (1 línea por tipo) ---
        foreach ($pedido->cefalometrias as $c) {
            $tipo = (string) $c->tipo;
            $items[] = [
                'key'         => 'cefa:' . $tipo,
                'label'       => 'Cefalometría - ' . ($cefalometriasTipos[$tipo] ?? $tipo),
                'grupo'       => 'Cefalometrías',
                'cantidad'    => 1,
                'observacion' => null,
            ];
        }

        // --- Piezas agrupadas (para no explotar en 50 filas) ---
        $periapicales = $pedido->piezas->where('tipo', 'periapical')->pluck('pieza_codigo')->map(fn($v) => (string)$v)->values()->all();
        if (count($periapicales) > 0) {
            $items[] = [
                'key'         => 'pieza:periapical',
                'label'       => 'Piezas periapicales seleccionadas',
                'grupo'       => 'Piezas',
                'cantidad'    => count($periapicales),
                'observacion' => implode(', ', $periapicales),
            ];
        }

        $tomografias = $pedido->piezas->where('tipo', 'tomografia')->pluck('pieza_codigo')->map(fn($v) => (string)$v)->values()->all();
        if (count($tomografias) > 0) {
            $items[] = [
                'key'         => 'pieza:tomografia',
                'label'       => 'Piezas tomografía seleccionadas',
                'grupo'       => 'Piezas',
                'cantidad'    => count($tomografias),
                'observacion' => implode(', ', $tomografias),
            ];
        }

        return $items;
    }

    private function conceptLabelMap(): array
    {
        return [
            'rx_panoramica_convencional' => ['label' => 'Panorámica - Convencional', 'grupo' => 'RX', 'obs' => null],
            'rx_panoramica_trazado_implante' => [
                'label' => 'Panorámica - Con trazado p/ implante',
                'grupo' => 'RX',
                'obs'   => fn(Pedido $p) => $p->rx_panoramica_trazado_region ? ('Región: ' . $p->rx_panoramica_trazado_region) : null,
            ],
            'rx_panoramica_atm_boca_abierta_cerrada' => ['label' => 'Panorámica - ATM (boca abierta y cerrada)', 'grupo' => 'RX', 'obs' => null],
            'rx_teleradiografia_lateral' => ['label' => 'Teleradiografía - Lateral', 'grupo' => 'RX', 'obs' => null],
            'rx_teleradiografia_frontal_pa' => ['label' => 'Teleradiografía - Frontal (PA)', 'grupo' => 'RX', 'obs' => null],
            'rx_teleradiografia_waters' => ['label' => 'Teleradiografía - Waters', 'grupo' => 'RX', 'obs' => null],
            'rx_teleradiografia_indice_carpal_edad_osea' => ['label' => 'Teleradiografía - Índice carpal y edad ósea', 'grupo' => 'RX', 'obs' => null],
            'rx_interproximal_premolares_derecho' => ['label' => 'Interproximal - Pre-molares derecho', 'grupo' => 'RX', 'obs' => null],
            'rx_interproximal_premolares_izquierdo' => ['label' => 'Interproximal - Pre-molares izquierdo', 'grupo' => 'RX', 'obs' => null],
            'rx_interproximal_molares_derecho' => ['label' => 'Interproximal - Molares derecho', 'grupo' => 'RX', 'obs' => null],
            'rx_interproximal_molares_izquierdo' => ['label' => 'Interproximal - Molares izquierdo', 'grupo' => 'RX', 'obs' => null],
            'rx_periapical_dientes_senalados' => [
                'label' => 'Periapical - Dientes señalados',
                'grupo' => 'RX',
                'obs'   => fn(Pedido $p) => $p->rx_periapical_region ? ('Región: ' . $p->rx_periapical_region) : null,
            ],
            'rx_periapical_status_radiografico' => ['label' => 'Periapical - Status radiográfico (todos)', 'grupo' => 'RX', 'obs' => null],
            'rx_periapical_tecnica_clark' => ['label' => 'Periapical - Técnica de Clark', 'grupo' => 'RX', 'obs' => null],

            'intraoral_maxilar_superior' => ['label' => 'Escaneamiento intraoral - Maxilar superior', 'grupo' => 'Intraoral', 'obs' => null],
            'intraoral_mandibula' => ['label' => 'Escaneamiento intraoral - Mandíbula', 'grupo' => 'Intraoral', 'obs' => null],
            'intraoral_maxilar_mandibula_completa' => ['label' => 'Escaneamiento intraoral - Maxilar y mandíbula completa', 'grupo' => 'Intraoral', 'obs' => null],
            'intraoral_modelo_con_base' => ['label' => 'Intraoral - Modelo con base (Estudio)', 'grupo' => 'Intraoral', 'obs' => null],
            'intraoral_modelo_sin_base' => ['label' => 'Intraoral - Modelo sin base (Trabajo)', 'grupo' => 'Intraoral', 'obs' => null],

            'ct_maxilar_completa' => ['label' => 'Tomografía - Maxilar completa', 'grupo' => 'CT', 'obs' => null],
            'ct_mandibula_completa' => ['label' => 'Tomografía - Mandíbula completa', 'grupo' => 'CT', 'obs' => null],
            'ct_maxilar_arco_cigomatico' => ['label' => 'Tomografía - Maxilar (arco cigomático)', 'grupo' => 'CT', 'obs' => null],
            'ct_atm' => ['label' => 'Tomografía - ATM', 'grupo' => 'CT', 'obs' => null],
            'ct_parcial' => [
                'label' => 'Tomografía - Parcial',
                'grupo' => 'CT',
                'obs'   => fn(Pedido $p) => $p->ct_parcial_zona ? ('Zona: ' . $p->ct_parcial_zona) : null,
            ],
            'ct_region_senalada_abajo' => ['label' => 'Tomografía - Región señalada abajo', 'grupo' => 'CT', 'obs' => null],

            'entrega_pdf' => ['label' => 'Entrega - Digital (PDF)', 'grupo' => 'Entrega', 'obs' => null],
            'entrega_papel_fotografico' => ['label' => 'Entrega - Papel fotográfico', 'grupo' => 'Entrega', 'obs' => null],
            'entrega_dicom' => ['label' => 'Entrega - DICOM', 'grupo' => 'Entrega', 'obs' => null],
            'entrega_software_visualizacion' => [
                'label' => 'Entrega - Software de visualización',
                'grupo' => 'Entrega',
                'obs'   => fn(Pedido $p) => $p->entrega_software_detalle ? ('Software: ' . $p->entrega_software_detalle) : null,
            ],
        ];
    }

    private function getCatalogos(): array
    {
        $fotosTipos = [
            'frente'            => 'Frente',
            'perfil_derecho'    => 'Perfil derecho',
            'perfil_izquierdo'  => 'Perfil izquierdo',
            'sonriendo'         => 'Sonriendo (1/3 inferior de la face)',
            'frontal_oclusion'  => 'Frontal en oclusión',
            'lateral_derecha'   => 'Lateral derecha',
            'lateral_izquierda' => 'Lateral izquierda',
            'oclusal_superior'  => 'Oclusal superior',
            'oclusal_inferior'  => 'Oclusal inferior',
        ];

        $cefalometriasTipos = [
            'usp' => 'Usp',
            'unicamp' => 'Unicamp',
            'usp_unicamp' => 'Usp/unicamp',
            'tweed' => 'Tweed',
            'steiner' => 'Steiner',
            'homem_neto' => 'Homem Neto',
            'downs' => 'Downs',
            'mcnamara' => 'McNamara',
            'bimler' => 'Bimler',
            'jarabak' => 'Jarabak',
            'profis' => 'Profis',
            'ricketts' => 'Ricketts',
            'ricketts_frontal' => 'Ricketts frontal',
            'petrovic' => 'Petrovic',
            'sassouni' => 'Sassouni',
            'schwarz' => 'Schwarz',
            'trevisi' => 'Trevisi',
            'valieri' => 'Valieri',
            'rocabado' => 'Rocabado',
            'adenoides' => 'Adenoides',
        ];

        $documentaciones = [
            'doc_simplificada_1' => 'DOC. Simplificada 1 (DIGITAL) – Panorámica, teleradiografía lateral, (8) fotos',
            'doc_simplificada_2' => 'DOC. Simplificada 2 (DIGITAL) – Panorámica, teleradiografía lateral (6) fotos',
            'doc_completa_digital' => 'Documentación completa (DIGITAL) – Panorámica, teleradiografía lateral (c/trazado), (8) fotos, escaneado intraoral',
            'doc_completa_fotos_modelo' => 'Documentación completa (fotografías y modelo con impresión) – Panorámica, teleradiografía lateral (c/trazado), (8) fotos, escaneado intraoral, modelo',
        ];

        return [$fotosTipos, $cefalometriasTipos, $documentaciones];
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Clinica;
use App\Models\Paciente;
use App\Models\Pedido;
use App\Models\PedidoFoto;
use App\Models\PedidoCefalometria;
use App\Models\PedidoPieza;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PedidoController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:pedidos.view')->only(['index', 'show']);
        $this->middleware('permission:pedidos.create')->only(['create', 'store']);
        $this->middleware('permission:pedidos.update')->only(['edit', 'update']);
        $this->middleware('permission:pedidos.delete')->only(['destroy']);
    }

   
// Listado básico
public function index(Request $request)
{
    $search = trim((string) $request->get('search', ''));
    $estado = trim((string) $request->get('estado', ''));

    $pedidos = Pedido::with(['clinica', 'paciente'])
        ->when($search !== '', function ($q) use ($search) {
            $q->where('codigo', 'like', "%{$search}%")
                ->orWhereHas('paciente', function ($w) use ($search) {
                    $w->where('nombre', 'like', "%{$search}%")
                        ->orWhere('apellido', 'like', "%{$search}%");
                });
        })
        ->when($estado !== '', fn ($q) => $q->where('estado', $estado))
        ->latest('id')
        ->paginate(20)
        ->withQueryString();

    // --------- VARIABLES PARA EL FORM (MODAL) ---------
    $pedido = new Pedido();

    $clinicas = Clinica::where('is_active', true)
        ->orderBy('nombre')
        ->get();

    $pacientes = Paciente::with('clinica')
        ->orderBy('apellido')
        ->orderBy('nombre')
        ->get();

    // mismos catálogos que en create()
    [$fotosTipos, $cefalometriasTipos, $documentaciones] = $this->getCatalogos();

    // sin nada seleccionado al abrir el modal
    $fotosSeleccionadas         = [];
    $cefalometriasSeleccionadas = [];
    $piezasSeleccionadas        = [];

    return view('admin.pedidos.index', compact(
        'pedidos',
        'search',
        'estado',
      
        'pedido',
        'clinicas',
        'pacientes',
        'fotosTipos',
        'cefalometriasTipos',
        'documentaciones',
        'fotosSeleccionadas',
        'cefalometriasSeleccionadas',
        'piezasSeleccionadas'
    ));
}


    // Form crear
    public function create(Request $request)
    {
        $pedido = new Pedido();

        $clinicas = Clinica::where('is_active', true)
            ->orderBy('nombre')
            ->get();

        $pacientes = Paciente::with('clinica')
            ->orderBy('apellido')
            ->orderBy('nombre')
            ->get();

        // Listas para checkboxes
        [$fotosTipos, $cefalometriasTipos, $documentaciones] = $this->getCatalogos();

        $fotosSeleccionadas        = [];
        $cefalometriasSeleccionadas = [];
        $piezasSeleccionadas       = [];

        return view('admin.pedidos.create', compact(
            'pedido',
            'clinicas',
            'pacientes',
            'fotosTipos',
            'cefalometriasTipos',
            'documentaciones',
            'fotosSeleccionadas',
            'cefalometriasSeleccionadas',
            'piezasSeleccionadas'
        ));
    }

    // Guardar
    public function store(Request $request)
    {
        [$fotosTipos, $cefalometriasTipos, $documentaciones] = $this->getCatalogos();

        $data = $request->validate([
            'clinica_id'        => ['required', 'integer', 'exists:clinicas,id'],
            'paciente_id'       => ['required', 'integer', 'exists:pacientes,id'],
            'consulta_id'       => ['nullable', 'integer', 'exists:consultas,id'],
            'prioridad'         => ['nullable', Rule::in(['normal', 'urgente'])],
            'fecha_agendada'    => ['nullable', 'date'],
            'hora_agendada'     => ['nullable', 'date_format:H:i'],
            'doctor_nombre'     => ['nullable', 'string', 'max:120'],
            'doctor_telefono'   => ['nullable', 'string', 'max:50'],
            'doctor_email'      => ['nullable', 'string', 'max:120'],
            'paciente_documento' => ['nullable', 'string', 'max:50'],
            'direccion'         => ['nullable', 'string', 'max:255'],

            'rx_panoramica_trazado_region' => ['nullable', 'string', 'max:120'],
            'rx_periapical_region'         => ['nullable', 'string', 'max:150'],

            'ct_parcial_zona'              => ['nullable', 'string', 'max:150'],
            'entrega_software_detalle'     => ['nullable', 'string', 'max:150'],

            'documentacion_tipo' => ['nullable', Rule::in(array_keys($documentaciones))],

            'descripcion_caso'   => ['nullable', 'string'],

            'fotos'              => ['nullable', 'array'],
            'fotos.*'            => ['string', Rule::in(array_keys($fotosTipos))],

            'cefalometrias'      => ['nullable', 'array'],
            'cefalometrias.*'    => ['string', Rule::in(array_keys($cefalometriasTipos))],

            // CSV de piezas desde el odontograma
            'piezas_codigos'     => ['nullable', 'string'],
        ]);

        $pedido = new Pedido();

        $pedido->clinica_id         = $data['clinica_id'];
        $pedido->paciente_id        = $data['paciente_id'];
        $pedido->consulta_id        = $data['consulta_id'] ?? null;
        $pedido->created_by         = Auth::id();
        $pedido->codigo             = $this->generarCodigo();
        $pedido->estado             = 'pendiente';
        $pedido->prioridad          = $data['prioridad'] ?? 'normal';
        $pedido->fecha_solicitud    = now()->toDateString();
        $pedido->fecha_agendada     = $data['fecha_agendada'] ?? null;
        $pedido->hora_agendada      = $data['hora_agendada'] ?? null;

        $pedido->doctor_nombre      = $data['doctor_nombre'] ?? null;
        $pedido->doctor_telefono    = $data['doctor_telefono'] ?? null;
        $pedido->doctor_email       = $data['doctor_email'] ?? null;
        $pedido->paciente_documento = $data['paciente_documento'] ?? null;
        $pedido->direccion          = $data['direccion'] ?? null;

        $pedido->rx_panoramica_trazado_region = $data['rx_panoramica_trazado_region'] ?? null;
        $pedido->rx_periapical_region         = $data['rx_periapical_region'] ?? null;
        $pedido->ct_parcial_zona              = $data['ct_parcial_zona'] ?? null;
        $pedido->entrega_software_detalle     = $data['entrega_software_detalle'] ?? null;

        $pedido->documentacion_tipo           = $data['documentacion_tipo'] ?? null;
        $pedido->descripcion_caso             = $data['descripcion_caso'] ?? null;

        // Booleans: tomados desde checkboxes
        foreach ($this->booleanFields() as $field) {
            $pedido->{$field} = $request->boolean($field);
        }

        $pedido->save();

        // Fotos
        $fotos = $request->input('fotos', []);
        foreach ($fotos as $tipo) {
            PedidoFoto::create([
                'pedido_id' => $pedido->id,
                'tipo'      => $tipo,
            ]);
        }

        // Cefalometrías
        $cefas = $request->input('cefalometrias', []);
        foreach ($cefas as $tipo) {
            PedidoCefalometria::create([
                'pedido_id' => $pedido->id,
                'tipo'      => $tipo,
            ]);
        }

        // Piezas desde odontograma (CSV → array)
        $piezasCodigos = collect(explode(',', (string) $request->input('piezas_codigos', '')))
            ->map(fn($v) => trim($v))
            ->filter()
            ->unique()
            ->values();

        foreach ($piezasCodigos as $pieza) {
            PedidoPieza::create([
                'pedido_id'    => $pedido->id,
                'pieza_codigo' => $pieza,
                'tipo'         => 'periapical',
            ]);
        }

        return redirect()
            ->route('admin.pedidos.index')
            ->with('success', 'Pedido creado correctamente.');
    }

    // Mostrar detalle simple (lo podemos enriquecer luego)
    public function show(Pedido $pedido)
    {
        $pedido->load(['clinica', 'paciente', 'fotos', 'cefalometrias', 'piezas']);

        return view('admin.pedidos.show', compact('pedido'));
    }

    // Form editar
    public function edit(Pedido $pedido)
    {
        $pedido->load(['fotos', 'cefalometrias', 'piezas']);

        $clinicas = Clinica::where('is_active', true)
            ->orderBy('nombre')
            ->get();

        $pacientes = Paciente::with('clinica')
            ->orderBy('apellido')
            ->orderBy('nombre')
            ->get();

        [$fotosTipos, $cefalometriasTipos, $documentaciones] = $this->getCatalogos();

        $fotosSeleccionadas         = $pedido->fotos->pluck('tipo')->all();
        $cefalometriasSeleccionadas = $pedido->cefalometrias->pluck('tipo')->all();
        $piezasSeleccionadas        = $pedido->piezas->pluck('pieza_codigo')->all();

        return view('admin.pedidos.edit', compact(
            'pedido',
            'clinicas',
            'pacientes',
            'fotosTipos',
            'cefalometriasTipos',
            'documentaciones',
            'fotosSeleccionadas',
            'cefalometriasSeleccionadas',
            'piezasSeleccionadas'
        ));
    }

    // Actualizar
    public function update(Request $request, Pedido $pedido)
    {
        [$fotosTipos, $cefalometriasTipos, $documentaciones] = $this->getCatalogos();

        $data = $request->validate([
            'clinica_id'        => ['required', 'integer', 'exists:clinicas,id'],
            'paciente_id'       => ['required', 'integer', 'exists:pacientes,id'],
            'consulta_id'       => ['nullable', 'integer', 'exists:consultas,id'],
            'prioridad'         => ['nullable', Rule::in(['normal', 'urgente'])],
            'fecha_agendada'    => ['nullable', 'date'],
            'hora_agendada'     => ['nullable', 'date_format:H:i'],
            'doctor_nombre'     => ['nullable', 'string', 'max:120'],
            'doctor_telefono'   => ['nullable', 'string', 'max:50'],
            'doctor_email'      => ['nullable', 'string', 'max:120'],
            'paciente_documento' => ['nullable', 'string', 'max:50'],
            'direccion'         => ['nullable', 'string', 'max:255'],

            'rx_panoramica_trazado_region' => ['nullable', 'string', 'max:120'],
            'rx_periapical_region'         => ['nullable', 'string', 'max:150'],

            'ct_parcial_zona'              => ['nullable', 'string', 'max:150'],
            'entrega_software_detalle'     => ['nullable', 'string', 'max:150'],

            'documentacion_tipo' => ['nullable', Rule::in(array_keys($documentaciones))],
            'descripcion_caso'   => ['nullable', 'string'],

            'fotos'              => ['nullable', 'array'],
            'fotos.*'            => ['string', Rule::in(array_keys($fotosTipos))],

            'cefalometrias'      => ['nullable', 'array'],
            'cefalometrias.*'    => ['string', Rule::in(array_keys($cefalometriasTipos))],

            'piezas_codigos'     => ['nullable', 'string'],
        ]);

        $pedido->clinica_id         = $data['clinica_id'];
        $pedido->paciente_id        = $data['paciente_id'];
        $pedido->consulta_id        = $data['consulta_id'] ?? null;
        $pedido->prioridad          = $data['prioridad'] ?? 'normal';
        $pedido->fecha_agendada     = $data['fecha_agendada'] ?? null;
        $pedido->hora_agendada      = $data['hora_agendada'] ?? null;

        $pedido->doctor_nombre      = $data['doctor_nombre'] ?? null;
        $pedido->doctor_telefono    = $data['doctor_telefono'] ?? null;
        $pedido->doctor_email       = $data['doctor_email'] ?? null;
        $pedido->paciente_documento = $data['paciente_documento'] ?? null;
        $pedido->direccion          = $data['direccion'] ?? null;

        $pedido->rx_panoramica_trazado_region = $data['rx_panoramica_trazado_region'] ?? null;
        $pedido->rx_periapical_region         = $data['rx_periapical_region'] ?? null;
        $pedido->ct_parcial_zona              = $data['ct_parcial_zona'] ?? null;
        $pedido->entrega_software_detalle     = $data['entrega_software_detalle'] ?? null;

        $pedido->documentacion_tipo           = $data['documentacion_tipo'] ?? null;
        $pedido->descripcion_caso             = $data['descripcion_caso'] ?? null;

        foreach ($this->booleanFields() as $field) {
            $pedido->{$field} = $request->boolean($field);
        }

        $pedido->save();

        // Sincronizar hijos (borramos y recreamos, más fácil por ahora)
        $pedido->fotos()->delete();
        $pedido->cefalometrias()->delete();
        $pedido->piezas()->delete();

        $fotos = $request->input('fotos', []);
        foreach ($fotos as $tipo) {
            PedidoFoto::create([
                'pedido_id' => $pedido->id,
                'tipo'      => $tipo,
            ]);
        }

        $cefas = $request->input('cefalometrias', []);
        foreach ($cefas as $tipo) {
            PedidoCefalometria::create([
                'pedido_id' => $pedido->id,
                'tipo'      => $tipo,
            ]);
        }

        $piezasCodigos = collect(explode(',', (string) $request->input('piezas_codigos', '')))
            ->map(fn($v) => trim($v))
            ->filter()
            ->unique()
            ->values();

        foreach ($piezasCodigos as $pieza) {
            PedidoPieza::create([
                'pedido_id'    => $pedido->id,
                'pieza_codigo' => $pieza,
                'tipo'         => 'periapical',
            ]);
        }

        return redirect()
            ->route('admin.pedidos.index')
            ->with('success', 'Pedido actualizado correctamente.');
    }

    public function destroy(Pedido $pedido)
    {
        $pedido->delete();

        return redirect()
            ->route('admin.pedidos.index')
            ->with('success', 'Pedido eliminado.');
    }

    // ---------- helpers internos ----------

    protected function generarCodigo(): string
    {
        $year = now()->format('Y');
        $seq  = str_pad((string) (Pedido::whereYear('created_at', $year)->max('id') + 1), 6, '0', STR_PAD_LEFT);

        return "RAY-{$year}-{$seq}";
    }

    protected function getCatalogos(): array
    {
        $fotosTipos = [
            'frente'             => 'Frente',
            'perfil_derecho'     => 'Perfil derecho',
            'perfil_izquierdo'   => 'Perfil izquierdo',
            'sonriendo'          => 'Sonriendo (1/3 inferior de la face)',
            'frontal_oclusion'   => 'Frontal en oclusión',
            'lateral_derecha'    => 'Lateral derecha',
            'lateral_izquierda'  => 'Lateral izquierda',
            'oclusal_superior'   => 'Oclusal superior',
            'oclusal_inferior'   => 'Oclusal inferior',
        ];

        $cefalometriasTipos = [
            'usp'             => 'Usp',
            'unicamp'         => 'Unicamp',
            'usp_unicamp'     => 'Usp/unicamp',
            'tweed'           => 'Tweed',
            'steiner'         => 'Steiner',
            'homem_neto'      => 'Homem Neto',
            'downs'           => 'Downs',
            'mcnamara'        => 'McNamara',
            'bimler'          => 'Bimler',
            'jarabak'         => 'Jarabak',
            'profis'          => 'Profis',
            'ricketts'        => 'Ricketts',
            'ricketts_frontal' => 'Ricketts frontal',
            'petrovic'        => 'Petrovic',
            'sassouni'        => 'Sassouni',
            'schwarz'         => 'Schwarz',
            'trevisi'         => 'Trevisi',
            'valieri'         => 'Valieri',
            'rocabado'        => 'Rocabado',
            'adenoides'       => 'Adenoides',
        ];

        $documentaciones = [
            'doc_simplificada_1' => 'DOC. Simplificada 1 (DIGITAL) – Panorámica, teleradiografía lateral, (8) fotos',
            'doc_simplificada_2' => 'DOC. Simplificada 2 (DIGITAL) – Panorámica, teleradiografía lateral (6) fotos',
            'doc_completa_digital' => 'Documentación completa (DIGITAL) – Panorámica, teleradiografía lateral (c/trazado), (8) fotos, escaneado intraoral',
            'doc_completa_fotos_modelo' => 'Documentación completa (fotografías y modelo con impresión) – Panorámica, teleradiografía lateral (c/trazado), (8) fotos, escaneado intraoral, modelo',
        ];

        return [$fotosTipos, $cefalometriasTipos, $documentaciones];
    }

    protected function booleanFields(): array
    {
        return [
            // RX panorámica
            'rx_panoramica_convencional',
            'rx_panoramica_trazado_implante',
            'rx_panoramica_atm_boca_abierta_cerrada',

            // Teleradiografía
            'rx_teleradiografia_lateral',
            'rx_teleradiografia_frontal_pa',
            'rx_teleradiografia_waters',
            'rx_teleradiografia_indice_carpal_edad_osea',

            // Interproximal
            'rx_interproximal_premolares_derecho',
            'rx_interproximal_premolares_izquierdo',
            'rx_interproximal_molares_derecho',
            'rx_interproximal_molares_izquierdo',

            // Periapical
            'rx_periapical_dientes_senalados',
            'rx_periapical_status_radiografico',
            'rx_periapical_tecnica_clark',

            // Informe
            'rx_con_informe',

            // Intraoral
            'intraoral_maxilar_superior',
            'intraoral_mandibula',
            'intraoral_maxilar_mandibula_completa',
            'intraoral_modelo_con_base',
            'intraoral_modelo_sin_base',

            // Tomografía
            'ct_maxilar_completa',
            'ct_mandibula_completa',
            'ct_maxilar_arco_cigomatico',
            'ct_atm',
            'ct_parcial',
            'ct_region_senalada_abajo',

            // Formas de entrega
            'entrega_pdf',
            'entrega_papel_fotografico',
            'entrega_dicom',
            'entrega_software_visualizacion',

            // Finalidad del examen
            'finalidad_implantes',
            'finalidad_dientes_incluidos',
            'finalidad_terceros_molares',
            'finalidad_supernumerarios',
            'finalidad_perforacion_radicular',
            'finalidad_sospecha_fractura',
            'finalidad_patologia',
        ];
    }
}

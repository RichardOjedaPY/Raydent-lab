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
use Barryvdh\DomPDF\Facade\Pdf;

class PedidoController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:pedidos.view')->only(['index', 'show']);
        $this->middleware('permission:pedidos.create')->only(['create', 'store']);
        $this->middleware('permission:pedidos.update')->only(['edit', 'update']);
        $this->middleware('permission:pedidos.delete')->only(['destroy']);
    }

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
            ->when($estado !== '', fn($q) => $q->where('estado', $estado))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        // --------- VARIABLES PARA EL FORM (MODAL) ---------
        $pedido = new Pedido();
        $clinicas = Clinica::where('is_active', true)->orderBy('nombre')->get();
        $pacientes = Paciente::with('clinica')->orderBy('apellido')->orderBy('nombre')->get();
        [$fotosTipos, $cefalometriasTipos, $documentaciones] = $this->getCatalogos();

        // Arrays vacíos para el index/modal de creación
        $fotosSeleccionadas             = [];
        $cefalometriasSeleccionadas     = [];
        $piezasPeriapicalSeleccionadas  = []; // CORREGIDO
        $piezasTomografiaSeleccionadas  = []; // CORREGIDO
        $codigoPedidoSugerido = Pedido::generarCodigoPedido();

        // Modo 'create' para que el partial sepa qué botones mostrar
        $modo = 'create'; 

        return view('admin.pedidos.index', compact(
            'pedidos', 'search', 'estado', 'pedido', 'clinicas', 'pacientes',
            'fotosTipos', 'cefalometriasTipos', 'documentaciones',
            'fotosSeleccionadas', 'cefalometriasSeleccionadas',
            'piezasPeriapicalSeleccionadas', 'piezasTomografiaSeleccionadas',
            'codigoPedidoSugerido', 'modo'
        ));
    }

    public function create(Request $request)
    {
        $pedido = new Pedido();
        $clinicas = Clinica::where('is_active', true)->orderBy('nombre')->get();
        $pacientes = Paciente::with('clinica')->orderBy('apellido')->orderBy('nombre')->get();
        [$fotosTipos, $cefalometriasTipos, $documentaciones] = $this->getCatalogos();

        $fotosSeleccionadas             = [];
        $cefalometriasSeleccionadas     = [];
        $piezasPeriapicalSeleccionadas  = [];
        $piezasTomografiaSeleccionadas  = [];

        $codigoPedidoSugerido = Pedido::generarCodigoPedido();
        $modo = 'create';

        return view('admin.pedidos.create', compact(
            'pedido', 'clinicas', 'pacientes',
            'fotosTipos', 'cefalometriasTipos', 'documentaciones',
            'fotosSeleccionadas', 'cefalometriasSeleccionadas',
            'piezasPeriapicalSeleccionadas', 'piezasTomografiaSeleccionadas',
            'codigoPedidoSugerido', 'modo'
        ));
    }

    public function store(Request $request)
    {
        [$fotosTipos, $cefalometriasTipos, $documentaciones] = $this->getCatalogos();

        $data = $request->validate([
            'clinica_id'         => ['required', 'integer', 'exists:clinicas,id'],
            'paciente_id'        => ['required', 'integer', 'exists:pacientes,id'],
            'consulta_id'        => ['nullable', 'integer', 'exists:consultas,id'],
            'prioridad'          => ['nullable', Rule::in(['normal', 'urgente'])],
            'fecha_agendada'     => ['nullable', 'date'],
            'hora_agendada'      => ['nullable', 'date_format:H:i'],
            'doctor_nombre'      => ['nullable', 'string', 'max:120'],
            'doctor_telefono'    => ['nullable', 'string', 'max:50'],
            'doctor_email'       => ['nullable', 'string', 'max:120'],
            'paciente_documento' => ['nullable', 'string', 'max:50'],
            'direccion'          => ['nullable', 'string', 'max:255'],

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

            // CORRECCIÓN: Validar los inputs correctos del formulario
            'piezas_periapical_codigos' => ['nullable', 'string'],
            'piezas_tomografia_codigos' => ['nullable', 'string'],
        ]);

        $pedido = new Pedido();
        $pedido->fill($request->only([
            'clinica_id', 'paciente_id', 'consulta_id', 'prioridad',
            'doctor_nombre', 'doctor_telefono', 'doctor_email',
            'paciente_documento', 'direccion',
            'rx_panoramica_trazado_region', 'rx_periapical_region',
            'ct_parcial_zona', 'entrega_software_detalle',
            'documentacion_tipo', 'descripcion_caso'
        ]));

        $pedido->created_by      = Auth::id();
        $pedido->codigo          = $this->generarCodigo();
        $pedido->codigo_pedido   = Pedido::generarCodigoPedido();
        $pedido->estado          = 'pendiente';
        $pedido->fecha_solicitud = now()->toDateString();
        $pedido->fecha_agendada  = $data['fecha_agendada'] ?? null;
        $pedido->hora_agendada   = $data['hora_agendada'] ?? null;

        // Booleans
        foreach ($this->booleanFields() as $field) {
            $pedido->{$field} = $request->boolean($field);
        }

        $pedido->save();

        // Guardar relaciones (Fotos, Cefalo, Piezas)
        $this->guardarRelaciones($pedido, $request);

        return redirect()
            ->route('admin.pedidos.index')
            ->with('success', 'Pedido creado correctamente.');
    }

    public function edit(Pedido $pedido)
    {
        // CORRECCIÓN: Cargar solo piezas filtradas después en la vista o separarlas aquí
        $pedido->load(['fotos', 'cefalometrias', 'piezas']);

        $clinicas = Clinica::where('is_active', true)->orderBy('nombre')->get();
        $pacientes = Paciente::with('clinica')->orderBy('apellido')->orderBy('nombre')->get();
        [$fotosTipos, $cefalometriasTipos, $documentaciones] = $this->getCatalogos();

        $fotosSeleccionadas         = $pedido->fotos->pluck('tipo')->all();
        $cefalometriasSeleccionadas = $pedido->cefalometrias->pluck('tipo')->all();

        // CORRECCIÓN: Separar piezas por tipo para rellenar los odontogramas correctamente
        $piezasPeriapicalSeleccionadas = $pedido->piezas
            ->where('tipo', 'periapical')
            ->pluck('pieza_codigo')
            ->all();

        $piezasTomografiaSeleccionadas = $pedido->piezas
            ->where('tipo', 'tomografia')
            ->pluck('pieza_codigo')
            ->all();

        $modo = 'edit';
        $codigoPedidoSugerido = $pedido->codigo_pedido;

        return view('admin.pedidos.edit', compact(
            'pedido', 'clinicas', 'pacientes',
            'fotosTipos', 'cefalometriasTipos', 'documentaciones',
            'fotosSeleccionadas', 'cefalometriasSeleccionadas',
            'piezasPeriapicalSeleccionadas', 'piezasTomografiaSeleccionadas',
            'codigoPedidoSugerido', 'modo'
        ));
    }

    public function update(Request $request, Pedido $pedido)
    {
        [$fotosTipos, $cefalometriasTipos, $documentaciones] = $this->getCatalogos();

        $data = $request->validate([
            'clinica_id'         => ['required', 'integer', 'exists:clinicas,id'],
            'paciente_id'        => ['required', 'integer', 'exists:pacientes,id'],
            'consulta_id'        => ['nullable', 'integer', 'exists:consultas,id'],
            'prioridad'          => ['nullable', Rule::in(['normal', 'urgente'])],
            'fecha_agendada'     => ['nullable', 'date'],
            'hora_agendada'      => ['nullable', 'date_format:H:i'],
            'doctor_nombre'      => ['nullable', 'string', 'max:120'],
            'doctor_telefono'    => ['nullable', 'string', 'max:50'],
            'doctor_email'       => ['nullable', 'string', 'max:120'],
            'paciente_documento' => ['nullable', 'string', 'max:50'],
            'direccion'          => ['nullable', 'string', 'max:255'],

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

            // CORRECCIÓN: Nombres correctos del formulario
            'piezas_periapical_codigos' => ['nullable', 'string'],
            'piezas_tomografia_codigos' => ['nullable', 'string'],
        ]);

        $pedido->fill($request->only([
            'clinica_id', 'paciente_id', 'consulta_id', 'prioridad',
            'doctor_nombre', 'doctor_telefono', 'doctor_email',
            'paciente_documento', 'direccion',
            'rx_panoramica_trazado_region', 'rx_periapical_region',
            'ct_parcial_zona', 'entrega_software_detalle',
            'documentacion_tipo', 'descripcion_caso'
        ]));

        $pedido->fecha_agendada = $data['fecha_agendada'] ?? null;
        $pedido->hora_agendada  = $data['hora_agendada'] ?? null;

        foreach ($this->booleanFields() as $field) {
            $pedido->{$field} = $request->boolean($field);
        }

        $pedido->save();

        // Borrar relaciones antiguas y crear nuevas
        $pedido->fotos()->delete();
        $pedido->cefalometrias()->delete();
        $pedido->piezas()->delete();

        $this->guardarRelaciones($pedido, $request);

        return redirect()
            ->route('admin.pedidos.index')
            ->with('success', 'Pedido actualizado correctamente.');
    }

    // --- Helper privado para no repetir código en Store y Update ---
    private function guardarRelaciones(Pedido $pedido, Request $request)
    {
        // 1. Fotos
        $fotos = $request->input('fotos', []);
        foreach ($fotos as $tipo) {
            PedidoFoto::create(['pedido_id' => $pedido->id, 'tipo' => $tipo]);
        }

        // 2. Cefalometrías
        $cefas = $request->input('cefalometrias', []);
        foreach ($cefas as $tipo) {
            PedidoCefalometria::create(['pedido_id' => $pedido->id, 'tipo' => $tipo]);
        }

        // 3. Piezas Periapicales
        $periapicales = collect(explode(',', (string) $request->input('piezas_periapical_codigos', '')))
            ->map(fn($v) => trim($v))
            ->filter()
            ->unique();

        foreach ($periapicales as $pieza) {
            PedidoPieza::create([
                'pedido_id'    => $pedido->id,
                'pieza_codigo' => $pieza,
                'tipo'         => 'periapical', // Tipo explícito
            ]);
        }

        // 4. Piezas Tomografía
        $tomografias = collect(explode(',', (string) $request->input('piezas_tomografia_codigos', '')))
            ->map(fn($v) => trim($v))
            ->filter()
            ->unique();

        foreach ($tomografias as $pieza) {
            PedidoPieza::create([
                'pedido_id'    => $pedido->id,
                'pieza_codigo' => $pieza,
                'tipo'         => 'tomografia', // Tipo explícito
            ]);
        }
    }
    public function show(Pedido $pedido)
{
    $pedido->load([
        'clinica',
        'paciente',
        'fotos',
        'cefalometrias',
        'piezas',
        'archivos',         // ✅ archivos subidos por técnico
        'fotosRealizadas',  // ✅ fotos subidas por técnico
        'tecnico',
    ]);

    [$fotosTipos, $cefalometriasTipos, $documentaciones] = $this->getCatalogos();

    return view('admin.pedidos.show', compact(
        'pedido', 'fotosTipos', 'cefalometriasTipos', 'documentaciones'
    ));
}

    

    public function destroy(Pedido $pedido)
    {
        $pedido->delete();
        return redirect()->route('admin.pedidos.index')->with('success', 'Pedido eliminado.');
    }

    public function pdf(Pedido $pedido)
    {
        $pedido->load(['clinica', 'paciente']);

        // Separar piezas para el PDF
        $periapical = PedidoPieza::where('pedido_id', $pedido->id)
            ->where('tipo', 'periapical')
            ->pluck('pieza_codigo')
            ->map(fn ($v) => (string) $v)
            ->all();

        $tomografia = PedidoPieza::where('pedido_id', $pedido->id)
            ->where('tipo', 'tomografia')
            ->pluck('pieza_codigo')
            ->map(fn ($v) => (string) $v)
            ->all();

        $fotosSeleccionadas = PedidoFoto::where('pedido_id', $pedido->id)->pluck('tipo')->all();
        $cefalometriasSeleccionadas = PedidoCefalometria::where('pedido_id', $pedido->id)->pluck('tipo')->all();

        $pdf = Pdf::loadView('admin.pedidos.pdf', [
            'pedido'                     => $pedido,
            'periapical'                 => $periapical,
            'tomografia'                 => $tomografia,
            'fotosSeleccionadas'         => $fotosSeleccionadas,
            'cefalometriasSeleccionadas' => $cefalometriasSeleccionadas,
        ])->setPaper('a4');

        $nombre = 'pedido-' . ($pedido->codigo_pedido ?? $pedido->id) . '.pdf';
        return $pdf->stream($nombre);
    }

    // ... (Tus métodos auxiliares getCatalogos, booleanFields, generarCodigo se mantienen igual)
    // Solo asegúrate de NO incluir 'piezas_codigos' en ningún validate si no lo usas.
    protected function generarCodigo(): string {
        $year = now()->format('Y');
        $seq = str_pad((string) (Pedido::whereYear('created_at', $year)->max('id') + 1), 6, '0', STR_PAD_LEFT);
        return "RAY-{$year}-{$seq}";
    }

    protected function getCatalogos(): array {
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
            'usp' => 'Usp', 'unicamp' => 'Unicamp', 'usp_unicamp' => 'Usp/unicamp', 'tweed' => 'Tweed',
            'steiner' => 'Steiner', 'homem_neto' => 'Homem Neto', 'downs' => 'Downs', 'mcnamara' => 'McNamara',
            'bimler' => 'Bimler', 'jarabak' => 'Jarabak', 'profis' => 'Profis', 'ricketts' => 'Ricketts',
            'ricketts_frontal' => 'Ricketts frontal', 'petrovic' => 'Petrovic', 'sassouni' => 'Sassouni',
            'schwarz' => 'Schwarz', 'trevisi' => 'Trevisi', 'valieri' => 'Valieri', 'rocabado' => 'Rocabado',
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

    protected function booleanFields(): array {
        return [
            'rx_panoramica_convencional', 'rx_panoramica_trazado_implante', 'rx_panoramica_atm_boca_abierta_cerrada',
            'rx_teleradiografia_lateral', 'rx_teleradiografia_frontal_pa', 'rx_teleradiografia_waters', 'rx_teleradiografia_indice_carpal_edad_osea',
            'rx_interproximal_premolares_derecho', 'rx_interproximal_premolares_izquierdo', 'rx_interproximal_molares_derecho', 'rx_interproximal_molares_izquierdo',
            'rx_periapical_dientes_senalados', 'rx_periapical_status_radiografico', 'rx_periapical_tecnica_clark',
            'rx_con_informe',
            'intraoral_maxilar_superior', 'intraoral_mandibula', 'intraoral_maxilar_mandibula_completa', 'intraoral_modelo_con_base', 'intraoral_modelo_sin_base',
            'ct_maxilar_completa', 'ct_mandibula_completa', 'ct_maxilar_arco_cigomatico', 'ct_atm', 'ct_parcial', 'ct_region_senalada_abajo',
            'entrega_pdf', 'entrega_papel_fotografico', 'entrega_dicom', 'entrega_software_visualizacion',
            'finalidad_implantes', 'finalidad_dientes_incluidos', 'finalidad_terceros_molares', 'finalidad_supernumerarios', 'finalidad_perforacion_radicular', 'finalidad_sospecha_fractura', 'finalidad_patologia',
        ];
    }
}
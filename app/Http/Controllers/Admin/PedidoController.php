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
use App\Models\Consulta;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use App\Support\Sequence;



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
        $user    = $request->user();
        $isAdmin = $user->hasRole('admin');

        $search = trim((string) $request->get('search', ''));
        $estado = trim((string) $request->get('estado', ''));

        // 🔒 Multi-tenant: clínica solo ve su clínica
        $clinicaScopeId = $isAdmin ? (int) ($request->get('clinica_id') ?? 0) : (int) ($user->clinica_id ?? 0);

        $pedidos = Pedido::with(['clinica', 'paciente'])
            ->when($clinicaScopeId > 0, fn($q) => $q->where('clinica_id', $clinicaScopeId))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($w) use ($search) {
                    $w->where('codigo', 'like', "%{$search}%")
                        ->orWhere('codigo_pedido', 'like', "%{$search}%")
                        ->orWhereHas('paciente', function ($p) use ($search) {
                            $p->where('nombre', 'like', "%{$search}%")
                                ->orWhere('apellido', 'like', "%{$search}%");
                        });
                });
            })
            ->when($estado !== '', fn($q) => $q->where('estado', $estado))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        // --------- VARIABLES PARA EL FORM (MODAL) ---------
        $pedido = new Pedido();

        $clinicas = $isAdmin
            ? Clinica::where('is_active', true)->orderBy('nombre')->get()
            : Clinica::where('id', $user->clinica_id)->get();

        $pacientes = Paciente::with('clinica')
            ->when(! $isAdmin, fn($q) => $q->where('clinica_id', $user->clinica_id))
            ->orderBy('apellido')->orderBy('nombre')
            ->get();

        // Consultas (para asociar pedido a consulta)
        $consultas = Consulta::query()
            ->select('id', 'clinica_id', 'paciente_id', 'fecha_hora', 'motivo_consulta')
            ->when(! $isAdmin, fn($q) => $q->where('clinica_id', $user->clinica_id))
            ->orderByDesc('fecha_hora')
            ->limit(400)
            ->get();

        [$fotosTipos, $cefalometriasTipos, $documentaciones] = $this->getCatalogos();

        $fotosSeleccionadas             = [];
        $cefalometriasSeleccionadas     = [];
        $piezasPeriapicalSeleccionadas  = [];
        $piezasTomografiaSeleccionadas = [];
        $codigoPedidoSugerido = Pedido::sugerirCodigoPedido();

        $modo = 'create';

        return view('admin.pedidos.index', compact(
            'pedidos',
            'search',
            'estado',
            'pedido',
            'clinicas',
            'pacientes',
            'consultas',
            'fotosTipos',
            'cefalometriasTipos',
            'documentaciones',
            'fotosSeleccionadas',
            'cefalometriasSeleccionadas',
            'piezasPeriapicalSeleccionadas',
            'piezasTomografiaSeleccionadas',
            'codigoPedidoSugerido',
            'modo',
            'isAdmin',
            'clinicaScopeId'
        ));
    }


    public function create(Request $request)
    {
        $user    = $request->user();
        $isAdmin = $user->hasRole('admin');

        if (! $isAdmin && ! $user->clinica_id) {
            abort(403, 'Usuario clínica sin clínica asignada.');
        }

        $pedido = new Pedido();

        $clinicas = $isAdmin
            ? Clinica::where('is_active', true)->orderBy('nombre')->get()
            : Clinica::where('id', $user->clinica_id)->get();

        $pacientes = Paciente::with('clinica')
            ->when(! $isAdmin, fn($q) => $q->where('clinica_id', $user->clinica_id))
            ->orderBy('apellido')->orderBy('nombre')
            ->get();

        // Consultas (para asociar pedido a consulta)
        $consultas = Consulta::query()
            ->select('id', 'clinica_id', 'paciente_id', 'fecha_hora', 'motivo_consulta')
            ->when(! $isAdmin, fn($q) => $q->where('clinica_id', $user->clinica_id))
            ->orderByDesc('fecha_hora')
            ->limit(400)
            ->get();

        // ✅ Doctores (Owner / Admin clínica) de la clínica (o de todas si es admin)
        $doctores = User::query()
            ->select(['id', 'name', 'email', 'clinica_id', 'tipo_usuario_clinica'])
            ->whereNotNull('clinica_id')
            ->where('is_active', 1)
            ->where('tipo_usuario_clinica', 'owner') // 👈 solo Owners (Doctores)
            ->when(! $isAdmin, fn($q) => $q->where('clinica_id', $user->clinica_id))
            ->orderBy('name')
            ->get();

        [$fotosTipos, $cefalometriasTipos, $documentaciones] = $this->getCatalogos();

        $fotosSeleccionadas             = [];
        $cefalometriasSeleccionadas     = [];
        $piezasPeriapicalSeleccionadas  = [];
        $piezasTomografiaSeleccionadas  = [];

        $codigoPedidoSugerido = Pedido::sugerirCodigoPedido();

        $modo = 'create';

        return view('admin.pedidos.create', compact(
            'pedido',
            'clinicas',
            'pacientes',
            'consultas',
            'doctores', // ✅ NUEVO
            'fotosTipos',
            'cefalometriasTipos',
            'documentaciones',
            'fotosSeleccionadas',
            'cefalometriasSeleccionadas',
            'piezasPeriapicalSeleccionadas',
            'piezasTomografiaSeleccionadas',
            'codigoPedidoSugerido',
            'modo',
            'isAdmin'
        ));
    }





    public function edit(Pedido $pedido)
    {
        $user    = auth()->user();
        $isAdmin = $user->hasRole('admin');

        if (! $isAdmin && (int) $pedido->clinica_id !== (int) $user->clinica_id) {
            abort(403);
        }

        // Mantener cargas existentes
        $pedido->load(['fotos', 'cefalometrias', 'piezas']);

        // 🔒 Multi-tenant: clínicas/pacientes/consultas según rol
        $clinicas = $isAdmin
            ? Clinica::where('is_active', true)->orderBy('nombre')->get()
            : Clinica::where('id', $user->clinica_id)->get();

        $pacientes = Paciente::with('clinica')
            ->when(! $isAdmin, fn($q) => $q->where('clinica_id', $user->clinica_id))
            ->orderBy('apellido')
            ->orderBy('nombre')
            ->get();

        // ✅ Consultas disponibles para asociar (solo del scope)
        $consultas = Consulta::query()
            ->select('id', 'clinica_id', 'paciente_id', 'fecha_hora', 'motivo_consulta')
            ->when(! $isAdmin, fn($q) => $q->where('clinica_id', $user->clinica_id))
            ->orderByDesc('fecha_hora')
            ->limit(400)
            ->get();

        // ✅ Doctores (Owner / Admin clínica) de la clínica (o de todas si es admin)
        $doctores = User::query()
            ->select(['id', 'name', 'email', 'clinica_id', 'tipo_usuario_clinica'])
            ->whereNotNull('clinica_id')
            ->where('is_active', 1)
            ->where('tipo_usuario_clinica', 'owner') // 👈 solo Owners (Doctores)
            ->when(! $isAdmin, fn($q) => $q->where('clinica_id', $user->clinica_id))
            ->orderBy('name')
            ->get();

        [$fotosTipos, $cefalometriasTipos, $documentaciones] = $this->getCatalogos();

        $fotosSeleccionadas         = $pedido->fotos->pluck('tipo')->all();
        $cefalometriasSeleccionadas = $pedido->cefalometrias->pluck('tipo')->all();

        // Mantener separación por tipo (odontogramas)
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
            'pedido',
            'clinicas',
            'pacientes',
            'consultas',
            'doctores', // ✅ NUEVO
            'fotosTipos',
            'cefalometriasTipos',
            'documentaciones',
            'fotosSeleccionadas',
            'cefalometriasSeleccionadas',
            'piezasPeriapicalSeleccionadas',
            'piezasTomografiaSeleccionadas',
            'codigoPedidoSugerido',
            'modo',
            'isAdmin'
        ));
    }


    public function store(Request $request)
    {
        [$fotosTipos, $cefalometriasTipos, $documentaciones] = $this->getCatalogos();

        $data = $request->validate([
            'clinica_id'         => [auth()->user()->hasRole('admin') ? 'required' : 'nullable', 'integer', 'exists:clinicas,id'],
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

            'piezas_periapical_codigos' => ['nullable', 'string'],
            'piezas_tomografia_codigos' => ['nullable', 'string'],
        ]);

        $user    = $request->user();
        $isAdmin = $user->hasRole('admin');

        // 🔒 Multi-tenant: clínica no elige clinica_id
        if (! $isAdmin) {
            $data['clinica_id'] = (int) $user->clinica_id;
        }

        // ✅ Paciente debe pertenecer a clínica
        $paciente = Paciente::findOrFail($data['paciente_id']);
        if ((int) $paciente->clinica_id !== (int) $data['clinica_id']) {
            return back()
                ->withErrors(['paciente_id' => 'El paciente seleccionado no pertenece a la clínica indicada.'])
                ->withInput();
        }

        // ✅ Si se usa consulta_id, debe ser de la misma clínica y del mismo paciente
        if (! empty($data['consulta_id'])) {
            $consulta = Consulta::findOrFail($data['consulta_id']);
            if ((int) $consulta->clinica_id !== (int) $data['clinica_id'] || (int) $consulta->paciente_id !== (int) $data['paciente_id']) {
                return back()
                    ->withErrors(['consulta_id' => 'La consulta seleccionada no corresponde a ese paciente y clínica.'])
                    ->withInput();
            }
        }


        $pedido = new Pedido();

        // set seguro
        $pedido->clinica_id  = $data['clinica_id'];
        $pedido->paciente_id = $data['paciente_id'];
        $pedido->consulta_id = $data['consulta_id'] ?? null;

        // el resto igual
        $pedido->fill($request->only([
            'prioridad',
            'doctor_nombre',
            'doctor_telefono',
            'doctor_email',
            'paciente_documento',
            'direccion',
            'rx_panoramica_trazado_region',
            'rx_periapical_region',
            'ct_parcial_zona',
            'entrega_software_detalle',
            'documentacion_tipo',
            'descripcion_caso'
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
        $after = $this->snapshotRelaciones($pedido);

        Audit::log('pedidos', 'created', 'Pedido creado', $pedido, [
            'codigo_pedido' => $pedido->codigo_pedido ?? $pedido->id,
        ]);

        return redirect()
            ->route('admin.pedidos.index')
            ->with('success', 'Pedido creado correctamente.');
    }
    public function update(Request $request, Pedido $pedido)
    {
        $user    = $request->user();
        $isAdmin = $user->hasRole('admin');

        if (! $isAdmin && (int) $pedido->clinica_id !== (int) $user->clinica_id) {
            abort(403);
        }

        [$fotosTipos, $cefalometriasTipos, $documentaciones] = $this->getCatalogos();

        $data = $request->validate([
            'clinica_id'         => [$isAdmin ? 'required' : 'nullable', 'integer', 'exists:clinicas,id'],
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

            'piezas_periapical_codigos' => ['nullable', 'string'],
            'piezas_tomografia_codigos' => ['nullable', 'string'],
        ]);

        if (! $isAdmin) {
            $data['clinica_id'] = (int) $user->clinica_id;
        }

        $paciente = Paciente::findOrFail($data['paciente_id']);
        if ((int) $paciente->clinica_id !== (int) $data['clinica_id']) {
            return back()
                ->withErrors(['paciente_id' => 'El paciente seleccionado no pertenece a la clínica indicada.'])
                ->withInput();
        }

        if (! empty($data['consulta_id'])) {
            $consulta = Consulta::findOrFail($data['consulta_id']);
            if ((int) $consulta->clinica_id !== (int) $data['clinica_id'] || (int) $consulta->paciente_id !== (int) $data['paciente_id']) {
                return back()
                    ->withErrors(['consulta_id' => 'La consulta seleccionada no corresponde a ese paciente y clínica.'])
                    ->withInput();
            }
        }

        // ===== BEFORE (pedido + relaciones) =====
        $beforeRel = $this->snapshotRelaciones($pedido);

        // Set seguro (IDs)
        $pedido->clinica_id  = $data['clinica_id'];
        $pedido->paciente_id = $data['paciente_id'];
        $pedido->consulta_id = $data['consulta_id'] ?? null;

        // Resto
        $pedido->fill($request->only([
            'prioridad',
            'doctor_nombre',
            'doctor_telefono',
            'doctor_email',
            'paciente_documento',
            'direccion',
            'rx_panoramica_trazado_region',
            'rx_periapical_region',
            'ct_parcial_zona',
            'entrega_software_detalle',
            'documentacion_tipo',
            'descripcion_caso'
        ]));

        $pedido->fecha_agendada = $data['fecha_agendada'] ?? null;
        $pedido->hora_agendada  = $data['hora_agendada'] ?? null;

        foreach ($this->booleanFields() as $field) {
            $pedido->{$field} = $request->boolean($field);
        }

        // Capturar cambios “una letra” (solo campos modificados)
        $dirty = $pedido->getDirty();
        $beforeAttrs = Arr::only($pedido->getOriginal(), array_keys($dirty));

        $pedido->save();

        // AFTER de atributos (solo lo que cambió)
        if (!empty($dirty)) {
            $afterAttrs = Arr::only($pedido->fresh()->toArray(), array_keys($dirty));
            if ($beforeAttrs !== $afterAttrs) {
                Audit::log('pedidos', 'updated', 'Pedido actualizado', $pedido, [
                    'before' => $beforeAttrs,
                    'after'  => $afterAttrs,
                ]);
            }
        }

        // ===== Reset y recrear relaciones UNA sola vez =====
        $pedido->fotos()->delete();
        $pedido->cefalometrias()->delete();
        $pedido->piezas()->delete();

        $this->guardarRelaciones($pedido, $request);

        // ===== AFTER relaciones =====
        $pedido->load(['fotos', 'cefalometrias', 'piezas']);
        $afterRel = $this->snapshotRelaciones($pedido);

        if ($beforeRel !== $afterRel) {
            Audit::log('pedidos', 'relaciones_updated', 'Selecciones del pedido actualizadas', $pedido, [
                'before' => $beforeRel,
                'after'  => $afterRel,
            ]);
        }

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
        $user = auth()->user();

        // ✅ Reglas de acceso:
        // - admin: puede ver todo
        // - tecnico: puede ver todo (necesita ver lo solicitado por la clínica)
        // - clinica: solo puede ver pedidos de su propia clínica
        // - otros: 403
        $isAdmin   = $user->hasRole('admin');
        $isTecnico = $user->hasRole('tecnico');
        $isClinica = $user->hasRole('clinica');

        if (! $isAdmin && ! $isTecnico) {
            // si no es admin ni técnico, aplicamos restricción por clínica
            if ($isClinica) {
                if ((int) $pedido->clinica_id !== (int) $user->clinica_id) {
                    abort(403);
                }
            } else {
                abort(403);
            }
        }

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
            'pedido',
            'fotosTipos',
            'cefalometriasTipos',
            'documentaciones'
        ));
    }




    public function destroy(Pedido $pedido)
    {
        $user = auth()->user();
        if (! $user->hasRole('admin') && (int) $pedido->clinica_id !== (int) $user->clinica_id) {
            abort(403);
        }

        $snapshot = [
            'pedido' => [
                'id' => $pedido->id,
                'codigo_pedido' => $pedido->codigo_pedido ?? null,
                'clinica_id' => $pedido->clinica_id,
                'paciente_id' => $pedido->paciente_id,
                'estado' => $pedido->estado,
            ],
            'relaciones' => $this->snapshotRelaciones($pedido),
        ];

        Audit::log('pedidos', 'deleted', 'Pedido eliminado', $pedido, $snapshot);

        $pedido->delete();

        return redirect()
            ->route('admin.pedidos.index')
            ->with('success', 'Pedido eliminado.');
    }


    public function pdf(Pedido $pedido)
    {
        $user = auth()->user();

        // ✅ Acceso:
        // - admin: todo
        // - tecnico: todo (puede exportar)
        // - clinica: solo su clínica
        // - otros: 403
        $isAdmin   = $user->hasRole('admin');
        $isTecnico = $user->hasRole('tecnico');
        $isClinica = $user->hasRole('clinica');

        if (! $isAdmin && ! $isTecnico) {
            if ($isClinica) {
                if ((int) $pedido->clinica_id !== (int) $user->clinica_id) {
                    abort(403);
                }
            } else {
                abort(403);
            }
        }

        $pedido->load(['clinica', 'paciente']);

        // Separar piezas para el PDF
        $periapical = PedidoPieza::where('pedido_id', $pedido->id)
            ->where('tipo', 'periapical')
            ->pluck('pieza_codigo')
            ->map(fn($v) => (string) $v)
            ->all();

        $tomografia = PedidoPieza::where('pedido_id', $pedido->id)
            ->where('tipo', 'tomografia')
            ->pluck('pieza_codigo')
            ->map(fn($v) => (string) $v)
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
        Audit::log('pedidos', 'pdf', 'Pedido exportado a PDF', $pedido, [
            'codigo_pedido' => $pedido->codigo_pedido ?? $pedido->id,
        ]);

        return $pdf->stream($nombre);
    }



    protected function generarCodigo(): string
    {
        $year = now()->format('Y');
    
        $n = Sequence::next("pedidos:RAY:{$year}", function () use ($year) {
            return (int) DB::table('pedidos')
                ->where('codigo', 'like', "RAY-{$year}-%")
                ->selectRaw('MAX(CAST(SUBSTRING(codigo, 10) AS UNSIGNED)) as m')
                ->value('m');
        });
    
        return "RAY-{$year}-" . str_pad((string) $n, 6, '0', STR_PAD_LEFT);
    }
    


    protected function getCatalogos(): array
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

    protected function booleanFields(): array
    {
        return [
            'rx_panoramica_convencional',
            'rx_panoramica_trazado_implante',
            'rx_panoramica_atm_boca_abierta_cerrada',
            'rx_teleradiografia_lateral',
            'rx_teleradiografia_frontal_pa',
            'rx_teleradiografia_waters',
            'rx_teleradiografia_indice_carpal_edad_osea',
            'rx_interproximal_premolares_derecho',
            'rx_interproximal_premolares_izquierdo',
            'rx_interproximal_molares_derecho',
            'rx_interproximal_molares_izquierdo',
            'rx_periapical_dientes_senalados',
            'rx_periapical_status_radiografico',
            'rx_periapical_tecnica_clark',
            'rx_con_informe',
            'intraoral_maxilar_superior',
            'intraoral_mandibula',
            'intraoral_maxilar_mandibula_completa',
            'intraoral_modelo_con_base',
            'intraoral_modelo_sin_base',
            'ct_maxilar_completa',
            'ct_mandibula_completa',
            'ct_maxilar_arco_cigomatico',
            'ct_atm',
            'ct_parcial',
            'ct_region_senalada_abajo',
            'entrega_pdf',
            'entrega_papel_fotografico',
            'entrega_dicom',
            'entrega_software_visualizacion',
            'finalidad_implantes',
            'finalidad_dientes_incluidos',
            'finalidad_terceros_molares',
            'finalidad_supernumerarios',
            'finalidad_perforacion_radicular',
            'finalidad_sospecha_fractura',
            'finalidad_patologia',
        ];
    }
    private function snapshotRelaciones(Pedido $pedido): array
    {
        $pedido->loadMissing(['fotos', 'cefalometrias', 'piezas']);

        return [
            'fotos' => $pedido->fotos->pluck('tipo')->sort()->values()->all(),
            'cefalometrias' => $pedido->cefalometrias->pluck('tipo')->sort()->values()->all(),
            'periapical' => $pedido->piezas->where('tipo', 'periapical')->pluck('pieza_codigo')->sort()->values()->all(),
            'tomografia' => $pedido->piezas->where('tipo', 'tomografia')->pluck('pieza_codigo')->sort()->values()->all(),
        ];
    }
}

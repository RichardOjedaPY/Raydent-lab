@extends('layouts.admin')

@section('title', 'Ver Pedido')
@section('content_header', 'Detalle del Pedido')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.pedidos.index') }}">Pedidos</a></li>
    <li class="breadcrumb-item active">Ver</li>
@endsection

@section('content')
@php
    $fotosTipos         = $fotosTipos ?? [];
    $cefalometriasTipos = $cefalometriasTipos ?? [];
    $documentaciones    = $documentaciones ?? [];

    $estadoCls = match($pedido->estado) {
        'pendiente'  => 'badge badge-warning',
        'en_proceso' => 'badge badge-info',
        'listo'      => 'badge badge-success',
        'entregado'  => 'badge badge-primary',
        'cancelado'  => 'badge badge-danger',
        default      => 'badge badge-secondary',
    };

    $prioridad = $pedido->prioridad ?: 'normal';
    $prioridadCls = $prioridad === 'urgente' ? 'badge badge-danger' : 'badge badge-secondary';

    $chips = function (array $items, string $class = 'badge badge-pill badge-info') {
        $items = array_values(array_filter($items));
        if (!count($items)) return '<span class="text-muted">—</span>';
        return collect($items)->map(fn($t) => '<span class="'.$class.' mr-1 mb-1">'.$t.'</span>')->implode(' ');
    };

    $yesno = fn($ok) => $ok
        ? '<span class="badge badge-success">Sí</span>'
        : '<span class="badge badge-secondary">No</span>';

    // Piezas
    $piezasPeriapical = $pedido->piezas->where('tipo', 'periapical')->pluck('pieza_codigo')->map(fn($v)=>(string)$v)->values()->all();
    $piezasTomografia = $pedido->piezas->where('tipo', 'tomografia')->pluck('pieza_codigo')->map(fn($v)=>(string)$v)->values()->all();

    // Selecciones (solo TRUE)
    $rxPanoramica = array_values(array_filter([
        $pedido->rx_panoramica_convencional ? 'Convencional' : null,
        $pedido->rx_panoramica_trazado_implante ? 'Trazado implante' : null,
        $pedido->rx_panoramica_atm_boca_abierta_cerrada ? 'ATM boca abierta/cerrada' : null,
    ]));

    $rxTeleradio = array_values(array_filter([
        $pedido->rx_teleradiografia_lateral ? 'Lateral' : null,
        $pedido->rx_teleradiografia_frontal_pa ? 'Frontal PA' : null,
        $pedido->rx_teleradiografia_waters ? 'Waters' : null,
        $pedido->rx_teleradiografia_indice_carpal_edad_osea ? 'Índice carpal / edad ósea' : null,
    ]));

    $rxInterprox = array_values(array_filter([
        $pedido->rx_interproximal_premolares_derecho ? 'Premolares der.' : null,
        $pedido->rx_interproximal_premolares_izquierdo ? 'Premolares izq.' : null,
        $pedido->rx_interproximal_molares_derecho ? 'Molares der.' : null,
        $pedido->rx_interproximal_molares_izquierdo ? 'Molares izq.' : null,
    ]));

    $rxPeriapical = array_values(array_filter([
        $pedido->rx_periapical_dientes_senalados ? 'Dientes señalados' : null,
        $pedido->rx_periapical_status_radiografico ? 'Status radiográfico' : null,
        $pedido->rx_periapical_tecnica_clark ? 'Técnica Clark' : null,
    ]));

    $ct = array_values(array_filter([
        $pedido->ct_maxilar_completa ? 'Maxilar completa' : null,
        $pedido->ct_mandibula_completa ? 'Mandíbula completa' : null,
        $pedido->ct_maxilar_arco_cigomatico ? 'Arco cigomático' : null,
        $pedido->ct_atm ? 'ATM' : null,
        $pedido->ct_parcial ? 'Parcial' : null,
        $pedido->ct_region_senalada_abajo ? 'Región señalada abajo' : null,
    ]));

    $intraoral = array_values(array_filter([
        $pedido->intraoral_maxilar_superior ? 'Maxilar superior' : null,
        $pedido->intraoral_mandibula ? 'Mandíbula' : null,
        $pedido->intraoral_maxilar_mandibula_completa ? 'Maxilar + mandíbula completa' : null,
        $pedido->intraoral_modelo_con_base ? 'Modelo con base' : null,
        $pedido->intraoral_modelo_sin_base ? 'Modelo sin base' : null,
    ]));

    $entrega = array_values(array_filter([
        $pedido->entrega_pdf ? 'PDF' : null,
        $pedido->entrega_papel_fotografico ? 'Papel fotográfico' : null,
        $pedido->entrega_dicom ? 'DICOM' : null,
        $pedido->entrega_software_visualizacion ? 'Software visualización' : null,
    ]));

    $finalidad = array_values(array_filter([
        $pedido->finalidad_implantes ? 'Implantes' : null,
        $pedido->finalidad_dientes_incluidos ? 'Dientes incluidos' : null,
        $pedido->finalidad_terceros_molares ? '3ros molares' : null,
        $pedido->finalidad_supernumerarios ? 'Supernumerarios' : null,
        $pedido->finalidad_perforacion_radicular ? 'Perforación radicular' : null,
        $pedido->finalidad_sospecha_fractura ? 'Sospecha fractura' : null,
        $pedido->finalidad_patologia ? 'Patología' : null,
    ]));

    $docKey = $pedido->documentacion_tipo;
    $docLabel = $documentaciones[$docKey] ?? ($docKey ?: '—');

@endphp

<style>
    .kv-table td:first-child { width: 38%; color: #6c757d; }
    .chip-row { display:flex; flex-wrap:wrap; gap:.25rem; }
</style>

{{-- Header --}}
<div class="card card-outline card-primary mb-3">
    <div class="card-header">
        <div class="d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <div class="h5 mb-0">
                    Pedido <strong>{{ $pedido->codigo_pedido ?? $pedido->codigo ?? ('#'.$pedido->id) }}</strong>
                </div>
                <div class="text-muted small mt-1">
                    Código interno: <strong>{{ $pedido->codigo ?? '—' }}</strong>
                    <span class="mx-2">•</span>
                    Estado: <span class="{{ $estadoCls }}">{{ $pedido->estado ?? '—' }}</span>
                    <span class="mx-2">•</span>
                    Prioridad: <span class="{{ $prioridadCls }}">{{ ucfirst($prioridad) }}</span>
                </div>
            </div>

            <div class="mt-2 mt-md-0">
                <a href="{{ route('admin.pedidos.index') }}" class="btn btn-sm btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>

                @can('pedidos.update')
                    <a href="{{ route('admin.pedidos.edit', $pedido) }}" class="btn btn-sm btn-warning">
                        <i class="fas fa-edit"></i> Editar
                    </a>
                @endcan

                @can('pedidos.view')
                    @if(\Illuminate\Support\Facades\Route::has('admin.pedidos.pdf'))
                        <a href="{{ route('admin.pedidos.pdf', $pedido) }}" target="_blank" class="btn btn-sm btn-primary">
                            <i class="fas fa-file-pdf"></i> PDF
                        </a>
                    @endif
                @endcan
            </div>
        </div>
    </div>

    <div class="card-body p-0">
        {{-- Tabs --}}
        <ul class="nav nav-tabs px-3 pt-3" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" data-toggle="tab" href="#tab-resumen" role="tab">
                    <i class="fas fa-clipboard-list mr-1"></i> Resumen
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#tab-estudios" role="tab">
                    <i class="fas fa-x-ray mr-1"></i> Estudios
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#tab-complementos" role="tab">
                    <i class="fas fa-folder-open mr-1"></i> Complementos
                </a>
            </li>
        </ul>

        <div class="tab-content p-3">
            {{-- TAB 1: RESUMEN --}}
            <div class="tab-pane fade show active" id="tab-resumen" role="tabpanel">
                <div class="row">
                    <div class="col-lg-6">
                        <div class="card mb-3">
                            <div class="card-header"><i class="fas fa-info-circle mr-1"></i> Datos del pedido</div>
                            <div class="card-body p-0">
                                <table class="table table-sm table-borderless mb-0 kv-table">
                                    <tr>
                                        <td>Fecha solicitud</td>
                                        <td class="font-weight-bold">{{ $pedido->fecha_solicitud ?? optional($pedido->created_at)->format('Y-m-d') ?? '—' }}</td>
                                    </tr>
                                    <tr>
                                        <td>Agendado</td>
                                        <td class="font-weight-bold">
                                            {{ $pedido->fecha_agendada ?? '—' }}
                                            @if($pedido->hora_agendada) <span class="text-muted">· {{ $pedido->hora_agendada }}</span> @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Creado</td>
                                        <td class="font-weight-bold">
                                            {{ optional($pedido->created_at)->format('Y-m-d H:i') ?? '—' }}
                                            @if($pedido->created_by)
                                                <span class="text-muted">· User ID: {{ $pedido->created_by }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Consulta</td>
                                        <td class="font-weight-bold">{{ $pedido->consulta_id ?? '—' }}</td>
                                    </tr>
                                    <tr>
                                        <td>Dirección</td>
                                        <td class="font-weight-bold">{{ $pedido->direccion ?? '—' }}</td>
                                    </tr>
                                    <tr>
                                        <td>Con informe</td>
                                        <td>{!! $yesno((bool) $pedido->rx_con_informe) !!}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <div class="card mb-3">
                            <div class="card-header"><i class="fas fa-hospital mr-1"></i> Clínica</div>
                            <div class="card-body p-0">
                                <table class="table table-sm table-borderless mb-0 kv-table">
                                    <tr><td>Nombre</td><td class="font-weight-bold">{{ $pedido->clinica->nombre ?? '—' }}</td></tr>
                                    <tr><td>RUC</td><td class="font-weight-bold">{{ $pedido->clinica->ruc ?? '—' }}</td></tr>
                                    <tr><td>Teléfono</td><td class="font-weight-bold">{{ $pedido->clinica->telefono ?? '—' }}</td></tr>
                                    <tr><td>Email</td><td class="font-weight-bold">{{ $pedido->clinica->email ?? '—' }}</td></tr>
                                    <tr><td>Dirección</td><td class="font-weight-bold">{{ $pedido->clinica->direccion ?? '—' }}</td></tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card mb-3">
                            <div class="card-header"><i class="fas fa-user mr-1"></i> Paciente</div>
                            <div class="card-body p-0">
                                <table class="table table-sm table-borderless mb-0 kv-table">
                                    <tr>
                                        <td>Nombre</td>
                                        <td class="font-weight-bold">
                                            {{ $pedido->paciente->apellido ?? '' }} {{ $pedido->paciente->nombre ?? '' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Documento</td>
                                        <td class="font-weight-bold">
                                            {{ $pedido->paciente_documento ?? ($pedido->paciente->documento ?? '—') }}
                                        </td>
                                    </tr>
                                    <tr><td>Teléfono</td><td class="font-weight-bold">{{ $pedido->paciente->telefono ?? '—' }}</td></tr>
                                    <tr><td>Email</td><td class="font-weight-bold">{{ $pedido->paciente->email ?? '—' }}</td></tr>
                                </table>
                            </div>
                        </div>

                        <div class="card mb-0">
                            <div class="card-header"><i class="fas fa-user-md mr-1"></i> Doctor / Derivante</div>
                            <div class="card-body p-0">
                                <table class="table table-sm table-borderless mb-0 kv-table">
                                    <tr><td>Nombre</td><td class="font-weight-bold">{{ $pedido->doctor_nombre ?? '—' }}</td></tr>
                                    <tr><td>Teléfono</td><td class="font-weight-bold">{{ $pedido->doctor_telefono ?? '—' }}</td></tr>
                                    <tr><td>Email</td><td class="font-weight-bold">{{ $pedido->doctor_email ?? '—' }}</td></tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TAB 2: ESTUDIOS --}}
            <div class="tab-pane fade" id="tab-estudios" role="tabpanel">
                <div class="row">
                    <div class="col-lg-6">
                        <div class="card mb-3">
                            <div class="card-header"><i class="fas fa-x-ray mr-1"></i> Radiografías</div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <div class="text-muted small mb-1">Panorámica</div>
                                    <div class="chip-row">{!! $chips($rxPanoramica) !!}</div>
                                    @if($pedido->rx_panoramica_trazado_region)
                                        <div class="text-muted small mt-1">Región: {{ $pedido->rx_panoramica_trazado_region }}</div>
                                    @endif
                                </div>

                                <div class="mb-3">
                                    <div class="text-muted small mb-1">Teleradiografía</div>
                                    <div class="chip-row">{!! $chips($rxTeleradio) !!}</div>
                                </div>

                                <div class="mb-3">
                                    <div class="text-muted small mb-1">Interproximal</div>
                                    <div class="chip-row">{!! $chips($rxInterprox) !!}</div>
                                </div>

                                <div class="mb-0">
                                    <div class="text-muted small mb-1">Periapical</div>
                                    <div class="chip-row">{!! $chips($rxPeriapical) !!}</div>

                                    @if($pedido->rx_periapical_region)
                                        <div class="text-muted small mt-1">Región: {{ $pedido->rx_periapical_region }}</div>
                                    @endif

                                    <div class="mt-2">
                                        <div class="text-muted small mb-1">Piezas (Periapical)</div>
                                        <div class="chip-row">
                                            {!! $chips($piezasPeriapical, 'badge badge-pill badge-dark') !!}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card mb-3">
                            <div class="card-header"><i class="fas fa-layer-group mr-1"></i> Tomografía (CT)</div>
                            <div class="card-body">
                                <div class="chip-row">{!! $chips($ct) !!}</div>

                                @if($pedido->ct_parcial_zona)
                                    <div class="text-muted small mt-2">Zona parcial: {{ $pedido->ct_parcial_zona }}</div>
                                @endif

                                <div class="mt-3">
                                    <div class="text-muted small mb-1">Piezas (Tomografía)</div>
                                    <div class="chip-row">
                                        {!! $chips($piezasTomografia, 'badge badge-pill badge-dark') !!}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-0">
                            <div class="card-header"><i class="fas fa-tooth mr-1"></i> Escaneo intraoral</div>
                            <div class="card-body">
                                <div class="chip-row">{!! $chips($intraoral) !!}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TAB 3: COMPLEMENTOS --}}
            <div class="tab-pane fade" id="tab-complementos" role="tabpanel">
                <div class="row">
                    <div class="col-lg-6">
                        <div class="card mb-3">
                            <div class="card-header"><i class="fas fa-folder-open mr-1"></i> Documentación / Caso</div>
                            <div class="card-body">
                                <div class="text-muted small mb-1">Tipo de documentación</div>
                                <div class="font-weight-bold mb-3">{{ $docLabel }}</div>

                                <div class="text-muted small mb-1">Descripción del caso</div>
                                <div class="border rounded p-2" style="white-space: pre-wrap;">
                                    {{ $pedido->descripcion_caso ?: '—' }}
                                </div>
                            </div>
                        </div>

                        <div class="card mb-0">
                            <div class="card-header"><i class="fas fa-truck mr-1"></i> Entrega</div>
                            <div class="card-body">
                                <div class="chip-row">{!! $chips($entrega) !!}</div>
                                @if($pedido->entrega_software_detalle)
                                    <div class="text-muted small mt-2">Detalle software: {{ $pedido->entrega_software_detalle }}</div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card mb-3">
                            <div class="card-header"><i class="fas fa-bullseye mr-1"></i> Finalidad</div>
                            <div class="card-body">
                                <div class="chip-row">{!! $chips($finalidad) !!}</div>
                            </div>
                        </div>

                        <div class="card mb-0">
                            <div class="card-header"><i class="fas fa-camera mr-1"></i> Fotos y cefalometrías</div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3 mb-md-0">
                                        <div class="text-muted small mb-2">Fotos</div>
                                        @if($pedido->fotos->count())
                                            <ul class="mb-0 pl-3">
                                                @foreach($pedido->fotos as $f)
                                                    <li>{{ $fotosTipos[$f->tipo] ?? $f->tipo }}</li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <div class="text-muted">—</div>
                                        @endif
                                    </div>

                                    <div class="col-md-6">
                                        <div class="text-muted small mb-2">Cefalometrías</div>
                                        @if($pedido->cefalometrias->count())
                                            <ul class="mb-0 pl-3">
                                                @foreach($pedido->cefalometrias as $c)
                                                    <li>{{ $cefalometriasTipos[$c->tipo] ?? $c->tipo }}</li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <div class="text-muted">—</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

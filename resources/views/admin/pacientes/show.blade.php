@extends('layouts.admin')

@section('title', 'Paciente '.$paciente->nombre)
@section('content_header', 'Perfil del paciente')

@section('content')

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    <div class="mb-3">
        <a href="{{ route('admin.pacientes.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left mr-1"></i> Volver a pacientes
        </a>

        @can('pacientes.update')
            <a href="{{ route('admin.pacientes.edit', $paciente) }}" class="btn btn-primary btn-sm ml-1">
                <i class="fas fa-edit mr-1"></i> Editar
            </a>
        @endcan
    </div>

    @php
        $edadMostrada = $paciente->edad;
        if ($edadMostrada === null && $paciente->fecha_nacimiento) {
            $edadMostrada = $paciente->fecha_nacimiento->age;
        }
    @endphp

    <div class="row">
        <div class="col-lg-6">
            <div class="card mb-3">
                <div class="card-header">
                    Datos del paciente
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Nombre</dt>
                        <dd class="col-sm-8">{{ $paciente->nombre }} {{ $paciente->apellido }}</dd>

                        <dt class="col-sm-4">Documento</dt>
                        <dd class="col-sm-8">{{ $paciente->documento ?: '—' }}</dd>

                        <dt class="col-sm-4">Fecha nacimiento</dt>
                        <dd class="col-sm-8">
                            {{ $paciente->fecha_nacimiento?->format('d/m/Y') ?: '—' }}
                        </dd>

                        {{-- ✅ Edad --}}
                        <dt class="col-sm-4">Edad</dt>
                        <dd class="col-sm-8">{{ $edadMostrada !== null ? $edadMostrada.' años' : '—' }}</dd>

                        <dt class="col-sm-4">Género</dt>
                        <dd class="col-sm-8">
                            @switch($paciente->genero)
                                @case('M') Masculino @break
                                @case('F') Femenino @break
                                @case('O') Otro @break
                                @default — 
                            @endswitch
                        </dd>

                        <dt class="col-sm-4">Clínica</dt>
                        <dd class="col-sm-8">{{ $paciente->clinica?->nombre }}</dd>

                        <dt class="col-sm-4">Teléfono</dt>
                        <dd class="col-sm-8">{{ $paciente->telefono ?: '—' }}</dd>

                        <dt class="col-sm-4">Email</dt>
                        <dd class="col-sm-8">{{ $paciente->email ?: '—' }}</dd>

                        <dt class="col-sm-4">Dirección</dt>
                        <dd class="col-sm-8">{{ $paciente->direccion ?: '—' }}</dd>

                        <dt class="col-sm-4">Ciudad</dt>
                        <dd class="col-sm-8">{{ $paciente->ciudad ?: '—' }}</dd>

                        <dt class="col-sm-4">Estado</dt>
                        <dd class="col-sm-8">
                            @if($paciente->is_active)
                                <span class="badge badge-success">Activo</span>
                            @else
                                <span class="badge badge-secondary">Inactivo</span>
                            @endif
                        </dd>
                    </dl>
                </div>
            </div>
        </div>

        {{-- ✅ Historial real --}}
        <div class="col-lg-6">
            <div class="card mb-3">
                <div class="card-header">
                    Historial
                </div>
                <div class="card-body">

                    {{-- Consultas --}}
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong>Consultas</strong>
                        <span class="badge badge-info">{{ $consultas->count() }}</span>
                    </div>

                    @if($consultas->isEmpty())
                        <div class="text-muted mb-3">Sin consultas registradas.</div>
                    @else
                        <div class="table-responsive mb-3">
                            <table class="table table-sm table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Motivo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($consultas as $c)
                                    <tr>
                                        <td style="white-space:nowrap;">
                                            {{ $c->fecha_hora ? \Carbon\Carbon::parse($c->fecha_hora)->format('d/m/Y H:i') : '—' }}
                                        </td>
                                        <td>{{ $c->motivo_consulta ?: '—' }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    <hr>

                    {{-- Pedidos + Fotos --}}
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong>Pedidos y ficha fotográfica</strong>
                        <span class="badge badge-info">{{ $pedidos->count() }}</span>
                    </div>

                    @if($pedidos->isEmpty())
                        <div class="text-muted">Sin pedidos registrados.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Código</th>
                                        <th>Estado</th>
                                        <th class="text-center">Fotos</th>
                                        <th class="text-right">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($pedidos as $p)
                                    <tr>
                                        <td style="white-space:nowrap;">
                                            {{ $p->fecha_solicitud ? \Carbon\Carbon::parse($p->fecha_solicitud)->format('d/m/Y') : '—' }}
                                        </td>
                                        <td>{{ $p->codigo_pedido ?? $p->codigo ?? ('#'.$p->id) }}</td>
                                        <td>{{ $p->estado }}</td>
                                        <td class="text-center">
                                            <span class="badge badge-secondary">{{ (int) ($p->fotos_realizadas_count ?? 0) }}</span>
                                        </td>
                                        <td class="text-right" style="white-space:nowrap;">
                                            @if(\Illuminate\Support\Facades\Route::has('admin.pedidos.show'))
                                                <a class="btn btn-sm btn-outline-primary"
                                                   href="{{ route('admin.pedidos.show', $p) }}">
                                                    Ver
                                                </a>
                                            @endif

                                            @if(\Illuminate\Support\Facades\Route::has('admin.pedidos.fotos_pdf'))
                                                <a class="btn btn-sm btn-outline-success"
                                                   href="{{ route('admin.pedidos.fotos_pdf', $p) }}"
                                                   target="_blank">
                                                    PDF fotos
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
@endsection

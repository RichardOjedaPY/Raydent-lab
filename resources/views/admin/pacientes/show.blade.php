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

        {{-- Aquí luego agregaremos: consultas, pedidos, ficha fotográfica, etc. --}}
        <div class="col-lg-6">
            <div class="card mb-3">
                <div class="card-header">
                    Historial (placeholder)
                </div>
                <div class="card-body text-muted">
                    Aquí luego mostraremos consultas, pedidos y ficha fotográfica del paciente.
                </div>
            </div>
        </div>
    </div>
@endsection

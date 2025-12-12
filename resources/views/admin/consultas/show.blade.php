@extends('layouts.admin')

@section('title', 'Consulta de '.$consulta->paciente->nombre)
@section('content_header', 'Detalle de consulta')

@section('content')

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    <div class="mb-3">
        <a href="{{ route('admin.consultas.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left mr-1"></i> Volver a consultas
        </a>

        @can('consultas.update')
            <a href="{{ route('admin.consultas.edit', $consulta) }}" class="btn btn-primary btn-sm ml-1">
                <i class="fas fa-edit mr-1"></i> Editar
            </a>
        @endcan

        {{-- Más adelante: botón "Crear pedido para esta consulta/paciente" --}}
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card mb-3">
                <div class="card-header">
                    Datos generales
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Fecha / hora</dt>
                        <dd class="col-sm-8">{{ $consulta->fecha_hora?->format('d/m/Y H:i') }}</dd>

                        <dt class="col-sm-4">Paciente</dt>
                        <dd class="col-sm-8">
                            {{ $consulta->paciente?->nombre }} {{ $consulta->paciente?->apellido }}
                            <br>
                            <small class="text-muted">
                                Clínica: {{ $consulta->clinica?->nombre }}
                            </small>
                        </dd>

                        <dt class="col-sm-4">Profesional</dt>
                        <dd class="col-sm-8">{{ $consulta->profesional?->name ?? '—' }}</dd>

                        <dt class="col-sm-4">Motivo</dt>
                        <dd class="col-sm-8">{{ $consulta->motivo_consulta }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-6">

            <div class="card mb-3">
                <div class="card-header">Descripción del problema</div>
                <div class="card-body">
                    {!! nl2br(e($consulta->descripcion_problema ?? '—')) !!}
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">Antecedentes médicos</div>
                <div class="card-body">
                    {!! nl2br(e($consulta->antecedentes_medicos ?? '—')) !!}
                </div>
            </div>

        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card mb-3">
                <div class="card-header">Antecedentes odontológicos</div>
                <div class="card-body">
                    {!! nl2br(e($consulta->antecedentes_odontologicos ?? '—')) !!}
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">Medicamentos actuales</div>
                <div class="card-body">
                    {!! nl2br(e($consulta->medicamentos_actuales ?? '—')) !!}
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card mb-3">
                <div class="card-header">Alergias</div>
                <div class="card-body">
                    {!! nl2br(e($consulta->alergias ?? '—')) !!}
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">Diagnóstico presuntivo</div>
                <div class="card-body">
                    {!! nl2br(e($consulta->diagnostico_presuntivo ?? '—')) !!}
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">Plan de tratamiento / Observaciones</div>
                <div class="card-body">
                    {!! nl2br(e($consulta->plan_tratamiento ?? '—')) !!}
                    @if($consulta->observaciones)
                        <hr>
                        <strong>Notas:</strong><br>
                        {!! nl2br(e($consulta->observaciones)) !!}
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

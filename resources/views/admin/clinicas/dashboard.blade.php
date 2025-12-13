@extends('layouts.admin')

@section('title', 'Dashboard Clínica')
@section('content_header', 'Dashboard Clínica')

@section('content')
@php
    $badgeEstado = function ($estado) {
        return match($estado) {
            'pendiente'  => 'badge badge-warning',
            'en_proceso' => 'badge badge-info',
            'realizado'  => 'badge badge-success',
            'entregado'  => 'badge badge-primary',
            'cancelado'  => 'badge badge-danger',
            default      => 'badge badge-secondary',
        };
    };
@endphp

<div class="mb-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center">
        <div>
            <h4 class="mb-0">{{ $clinica->nombre ?? 'Clínica' }}</h4>
            <div class="text-muted small">
                RUC: <strong>{{ $clinica->ruc ?? '-' }}</strong>
                <span class="mx-2">·</span>
                Tel: <strong>{{ $clinica->telefono ?? '-' }}</strong>
            </div>
        </div>
    </div>
</div>

<div class="row">
    {{-- Pacientes --}}
    <div class="col-lg-3 col-md-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ number_format($pacientesTotal ?? 0, 0, ',', '.') }}</h3>
                <p>Pacientes registrados</p>
            </div>
            <div class="icon">
                <i class="fas fa-user-injured"></i>
            </div>
        </div>
    </div>

    {{-- Pendientes --}}
    <div class="col-lg-3 col-md-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ number_format($pedidosPendientes ?? 0, 0, ',', '.') }}</h3>
                <p>Pedidos pendientes</p>
            </div>
            <div class="icon">
                <i class="fas fa-hourglass-half"></i>
            </div>
        </div>
    </div>

    {{-- Finalizados --}}
    <div class="col-lg-3 col-md-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ number_format($pedidosFinalizados ?? 0, 0, ',', '.') }}</h3>
                <p>Pedidos finalizados</p>
            </div>
            <div class="icon">
                <i class="fas fa-check-circle"></i>
            </div>
        </div>
    </div>

    {{-- Total --}}
    <div class="col-lg-3 col-md-6">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3>{{ number_format($pedidosTotal ?? 0, 0, ',', '.') }}</h3>
                <p>Total de pedidos</p>
            </div>
            <div class="icon">
                <i class="fas fa-file-medical"></i>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">
            <i class="fas fa-list mr-1"></i> Últimos pedidos
        </h3>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Paciente</th>
                        <th>Prioridad</th>
                        <th>Estado</th>
                        <th class="text-right">Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ultimosPedidos as $p)
                        <tr>
                            <td>
                                <strong>{{ $p->codigo_pedido ?? $p->codigo ?? ('#'.$p->id) }}</strong>
                                @if($p->codigo)
                                    <div class="text-muted small">{{ $p->codigo }}</div>
                                @endif
                            </td>
                            <td>
                                {{ ($p->paciente->apellido ?? '').' '.($p->paciente->nombre ?? '') }}
                            </td>
                            <td>
                                @php
                                    $prio = $p->prioridad ?: 'normal';
                                    $prioCls = $prio === 'urgente' ? 'badge badge-danger' : 'badge badge-secondary';
                                @endphp
                                <span class="{{ $prioCls }}">{{ ucfirst($prio) }}</span>
                            </td>
                            <td>
                                <span class="{{ $badgeEstado($p->estado) }}">
                                    {{ $p->estado ?? '-' }}
                                </span>
                            </td>
                            <td class="text-right text-muted">
                                {{ optional($p->created_at)->format('Y-m-d H:i') ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">Sin pedidos aún.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

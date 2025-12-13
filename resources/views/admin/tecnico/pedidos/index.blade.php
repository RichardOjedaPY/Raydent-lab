@extends('layouts.admin')

@section('title', 'Pedidos (Técnico)')
@section('content_header', 'Pedidos (Técnico)')

@section('content')
@php
    // Badges de estado
    $badgeEstado = function ($estado) {
        return match($estado) {
            'pendiente'  => 'badge badge-warning', // amarillo
            'en_proceso' => 'badge badge-info',    // celeste
            'realizado'  => 'badge badge-success', // verde
            'entregado'  => 'badge badge-primary', // azul
            'cancelado'  => 'badge badge-danger',  // rojo
            default      => 'badge badge-secondary',
        };
    };

    // Badges de prioridad
    $badgePrioridad = function ($prioridad) {
        return match($prioridad) {
            'urgente' => 'badge badge-danger',
            'normal'  => 'badge badge-secondary',
            default   => 'badge badge-secondary',
        };
    };

    // Labels amigables
    $labelEstado = function ($estado) {
        return match($estado) {
            'pendiente'  => 'Pendiente',
            'en_proceso' => 'En proceso',
            'realizado'  => 'Realizado',
            'entregado'  => 'Entregado',
            'cancelado'  => 'Cancelado',
            default      => ucfirst((string) $estado),
        };
    };

    $labelPrioridad = fn ($p) => $p === 'urgente' ? 'Urgente' : 'Normal';
@endphp

<div class="card">
    <div class="card-header">
        <form class="form-inline" method="GET">
            <input name="q" value="{{ $q ?? '' }}"
                   class="form-control form-control-sm mr-2"
                   placeholder="Código o paciente">

            <input name="ci" value="{{ $ci ?? '' }}"
                   class="form-control form-control-sm mr-2"
                   placeholder="C.I. paciente">

            <select name="clinica_id" class="form-control form-control-sm mr-2">
                <option value="">-- Clínica --</option>
                @foreach($clinicas as $c)
                    <option value="{{ $c->id }}" @selected((int)($clinicaId ?? 0) === (int)$c->id)>
                        {{ $c->nombre }}
                    </option>
                @endforeach
            </select>

            <select name="estado" class="form-control form-control-sm mr-2">
                <option value="">-- Estado --</option>
                @foreach(['pendiente','en_proceso','realizado','entregado','cancelado'] as $e)
                    <option value="{{ $e }}" @selected(($estado ?? '') === $e)>{{ $e }}</option>
                @endforeach
            </select>

            <button class="btn btn-sm btn-primary">Filtrar</button>

            <a href="{{ route('admin.tecnico.pedidos.index') }}" class="btn btn-sm btn-light ml-2">
                Limpiar
            </a>
        </form>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Clínica</th>
                        <th>Paciente</th>
                        <th>Prioridad</th>
                        <th>Estado</th>
                        <th class="text-right">Acción</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($pedidos as $p)
                    <tr>
                        <td>
                            <strong>{{ $p->codigo_pedido ?? $p->codigo ?? ('#'.$p->id) }}</strong>
                            <div class="text-muted small">{{ $p->codigo ?? '' }}</div>
                        </td>

                        <td>{{ $p->clinica->nombre ?? '-' }}</td>

                        <td>{{ ($p->paciente->apellido ?? '').' '.($p->paciente->nombre ?? '') }}</td>

                        <td>
                            <span class="{{ $badgePrioridad($p->prioridad) }}">
                                {{ $labelPrioridad($p->prioridad) }}
                            </span>
                        </td>

                        <td>
                            <span class="{{ $badgeEstado($p->estado) }}">
                                {{ $labelEstado($p->estado) }}
                            </span>
                        </td>

                        <td class="text-right">
                            <a class="btn btn-sm btn-success" href="{{ route('admin.tecnico.pedidos.show', $p) }}">
                                Trabajar
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-3">Sin pedidos.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card-footer">
        {{ $pedidos->links() }}
    </div>
</div>
@endsection

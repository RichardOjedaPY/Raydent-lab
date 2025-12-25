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

    $fmt = fn($n) => number_format((int)($n ?? 0), 0, ',', '.');

    $kpi = function ($value, $label, $icon, $bg = 'bg-primary', $sub = null, $href = null) use ($fmt) {
        $v = $fmt($value);
        $subHtml = $sub ? "<div class=\"small opacity-75 mt-1\">{$sub}</div>" : '';
        $linkHtml = $href ? "<a href=\"{$href}\" class=\"kpi-link\">Ver detalle <i class=\"fas fa-arrow-circle-right ml-1\"></i></a>" : '';

        return <<<HTML
        <div class="kpi-card {$bg}">
            <div class="kpi-inner">
                <div class="kpi-meta">
                    <div class="kpi-label">{$label}</div>
                    <div class="kpi-value">{$v}</div>
                    {$subHtml}
                </div>
                <div class="kpi-icon">
                    <i class="{$icon}"></i>
                </div>
            </div>
            {$linkHtml}
        </div>
        HTML;
    };
@endphp

<style>
    /* ===== Estilo moderno (sin afectar AdminLTE) ===== */
    .dash-wrap { display: flex; flex-direction: column; gap: 1rem; }

    .hero {
        border: 1px solid rgba(148,163,184,.35);
        border-radius: 1rem;
        background: linear-gradient(180deg, rgba(255,255,255,1) 0%, rgba(248,250,252,1) 100%);
        box-shadow: 0 12px 30px rgba(15,23,42,.06);
        padding: 1rem 1.1rem;
    }
    .hero-title { font-size: 1.25rem; font-weight: 800; margin: 0; color: #111827; }
    .hero-sub { margin-top: .35rem; color: #64748b; font-size: .9rem; }
    .hero-sub strong { color: #0f172a; }

    .quick-actions { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: .85rem; }
    .qa-btn {
        border-radius: .75rem;
        padding: .45rem .75rem;
        border: 1px solid rgba(148,163,184,.35);
        background: #fff;
        color: #0f172a;
        font-weight: 700;
        font-size: .85rem;
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        text-decoration: none;
        box-shadow: 0 10px 24px rgba(15,23,42,.05);
    }
    .qa-btn:hover { text-decoration: none; filter: brightness(.98); }

    .kpi-grid { display: grid; grid-template-columns: repeat(12, 1fr); gap: .9rem; }
    .kpi-col { grid-column: span 12; }
    @media (min-width: 768px) { .kpi-col { grid-column: span 6; } }
    @media (min-width: 1200px){ .kpi-col { grid-column: span 3; } }

    .kpi-card {
        border-radius: 1rem;
        padding: .9rem .95rem;
        color: #fff;
        position: relative;
        overflow: hidden;
        box-shadow: 0 12px 30px rgba(15,23,42,.08);
        border: 1px solid rgba(255,255,255,.12);
        min-height: 110px;
    }
    .kpi-inner { display: flex; justify-content: space-between; gap: 1rem; }
    .kpi-label { font-size: .85rem; font-weight: 700; opacity: .95; }
    .kpi-value { font-size: 1.55rem; font-weight: 900; margin-top: .15rem; letter-spacing: .2px; }
    .kpi-icon {
        width: 46px; height: 46px;
        border-radius: .9rem;
        display: grid;
        place-items: center;
        background: rgba(255,255,255,.16);
        border: 1px solid rgba(255,255,255,.18);
        flex: 0 0 auto;
    }
    .kpi-icon i { font-size: 1.25rem; }
    .kpi-link {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        margin-top: .75rem;
        color: rgba(255,255,255,.92);
        font-weight: 800;
        font-size: .85rem;
        text-decoration: none;
    }
    .kpi-link:hover { text-decoration: none; color: #fff; }

    /* Colores */
    .bg-modern-info    { background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%); }
    .bg-modern-warning { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
    .bg-modern-success { background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); }
    .bg-modern-primary { background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); }
    .bg-modern-danger  { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }

    .card-soft{
        border: 1px solid rgba(148,163,184,.35);
        border-radius: 1rem;
        background: #fff;
        box-shadow: 0 12px 30px rgba(15,23,42,.06);
        overflow: hidden;
    }
    .card-soft .card-header{
        background: #fff;
        border-bottom: 1px solid rgba(148,163,184,.25);
    }

    .table thead th{
        background: #f8fafc;
        border-bottom: 1px solid rgba(148,163,184,.35);
        font-size: .85rem;
        color: #0f172a;
        white-space: nowrap;
    }
    .table td{ vertical-align: middle; }
    .nowrap{ white-space: nowrap; }
</style>

<div class="dash-wrap">

    {{-- HERO --}}
    <div class="hero">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
            <div>
                <h3 class="hero-title">{{ $clinica->nombre ?? 'Clínica' }}</h3>
                <div class="hero-sub">
                    RUC: <strong>{{ $clinica->ruc ?? '-' }}</strong>
                    <span class="mx-2">·</span>
                    Tel: <strong>{{ $clinica->telefono ?? '-' }}</strong>
                </div>

                <div class="quick-actions">
                    @can('pedidos.view')
                        <a class="qa-btn" href="{{ route('admin.pedidos.index') }}">
                            <i class="fas fa-file-medical"></i> Mis pedidos
                        </a>
                    @endcan

                    @can('pacientes.view')
                        <a class="qa-btn" href="{{ route('admin.pacientes.index') }}">
                            <i class="fas fa-user-injured"></i> Pacientes
                        </a>
                    @endcan

                    @can('estado_cuenta.view')
                        <a class="qa-btn" href="{{ route('admin.estado_cuenta.index', ['tab' => 'pendientes']) }}">
                            <i class="fas fa-receipt"></i> Estado de cuenta
                        </a>
                    @endcan
                </div>
            </div>

            <div class="text-muted small text-right">
                <div class="font-weight-bold text-dark">Resumen</div>
                <div>Actualizado: {{ now()->format('d/m/Y H:i') }}</div>
            </div>
        </div>
    </div>

    {{-- KPI GRID --}}
    <div class="kpi-grid">
        <div class="kpi-col">
            {!! $kpi($pacientesTotal, 'Pacientes registrados', 'fas fa-user-injured', 'bg-modern-info') !!}
        </div>

        <div class="kpi-col">
            {!! $kpi($pedidosPendientes, 'Pedidos pendientes', 'fas fa-hourglass-half', 'bg-modern-warning', 'Pendiente + En proceso') !!}
        </div>

        <div class="kpi-col">
            {!! $kpi($pedidosFinalizados, 'Pedidos finalizados', 'fas fa-check-circle', 'bg-modern-success', 'Realizado + Entregado') !!}
        </div>

        <div class="kpi-col">
            {!! $kpi($pedidosTotal, 'Total de pedidos', 'fas fa-file-medical', 'bg-modern-primary') !!}
        </div>

        {{-- ✅ NUEVO: Pagos pendientes / saldo --}}
        <div class="kpi-col">
            @php
                $sub = ($liquidacionesPendientesCount ?? 0) > 0
                    ? ($fmt($liquidacionesPendientesCount) . ' liquidación(es) con saldo')
                    : 'Sin saldos pendientes';

                $href = null;
                if(auth()->user()?->can('estado_cuenta.view')){
                    $href = route('admin.estado_cuenta.index', ['tab' => 'pendientes']);
                }
            @endphp

            {!! $kpi($saldoPendienteGs ?? 0, 'Saldo pendiente (pagos)', 'fas fa-money-bill-wave', 'bg-modern-danger', $sub, $href) !!}
        </div>
    </div>

    {{-- ÚLTIMOS PEDIDOS --}}
    <div class="card card-soft">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">
                <i class="fas fa-list mr-1"></i> Últimos pedidos
            </h3>

            @can('pedidos.view')
                <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.pedidos.index') }}">
                    Ver todos
                </a>
            @endcan
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
                                <td class="nowrap">
                                    <strong>{{ $p->codigo_pedido ?? $p->codigo ?? ('#'.$p->id) }}</strong>
                                    @if($p->codigo)
                                        <div class="text-muted small">{{ $p->codigo }}</div>
                                    @endif
                                </td>

                                <td>
                                    {{ ($p->paciente->apellido ?? '').' '.($p->paciente->nombre ?? '') }}
                                </td>

                                <td class="nowrap">
                                    @php
                                        $prio = $p->prioridad ?: 'normal';
                                        $prioCls = $prio === 'urgente' ? 'badge badge-danger' : 'badge badge-secondary';
                                    @endphp
                                    <span class="{{ $prioCls }}">{{ ucfirst($prio) }}</span>
                                </td>

                                <td class="nowrap">
                                    <span class="{{ $badgeEstado($p->estado) }}">
                                        {{ $p->estado ?? '-' }}
                                    </span>
                                </td>

                                <td class="text-right text-muted nowrap">
                                    {{ optional($p->created_at)->format('d/m/Y H:i') ?? '-' }}
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

</div>
@endsection

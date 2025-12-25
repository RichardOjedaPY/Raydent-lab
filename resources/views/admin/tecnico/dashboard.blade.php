@extends('layouts.admin')

@section('title', 'Dashboard (Técnico)')
@section('content_header', 'Dashboard (Técnico)')

@section('content')
@php
  $fmt = fn($n) => number_format((int)($n ?? 0), 0, ',', '.');
@endphp

<style>
  .dash-wrap{ display:flex; flex-direction:column; gap:1rem; }

  .hero{
    border: 1px solid rgba(148,163,184,.35);
    border-radius: 1rem;
    background: linear-gradient(180deg, rgba(255,255,255,1) 0%, rgba(248,250,252,1) 100%);
    box-shadow: 0 12px 30px rgba(15,23,42,.06);
    padding: 1rem 1.1rem;
  }
  .hero-title{ font-size: 1.15rem; font-weight: 900; margin: 0; color:#0f172a; }
  .hero-sub{ margin-top:.35rem; color:#64748b; font-size:.9rem; }

  .kpi-grid{ display:grid; grid-template-columns:repeat(12,1fr); gap:.9rem; }
  .kpi-col{ grid-column: span 12; }
  @media (min-width: 768px){ .kpi-col{ grid-column: span 6; } }
  @media (min-width: 1200px){ .kpi-col{ grid-column: span 3; } }

  .kpi-card{
    border-radius: 1rem;
    padding: .95rem 1rem;
    color:#fff;
    position:relative;
    overflow:hidden;
    box-shadow: 0 12px 30px rgba(15,23,42,.10);
    border: 1px solid rgba(255,255,255,.14);
    min-height: 112px;
  }
  .kpi-inner{ display:flex; justify-content:space-between; gap: 1rem; }
  .kpi-label{ font-size:.85rem; font-weight:800; opacity:.92; letter-spacing:.2px; }
  .kpi-value{ font-size:1.65rem; font-weight: 950; margin-top:.15rem; }
  .kpi-icon{
    width:46px; height:46px; border-radius:.9rem;
    display:grid; place-items:center;
    background: rgba(255,255,255,.16);
    border: 1px solid rgba(255,255,255,.20);
    flex: 0 0 auto;
  }
  .kpi-icon i{ font-size:1.25rem; }
  .kpi-link{
    display:inline-flex; align-items:center; gap:.35rem;
    margin-top:.75rem;
    color: rgba(255,255,255,.92);
    font-weight: 900;
    font-size:.85rem;
    text-decoration:none;
  }
  .kpi-link:hover{ color:#fff; text-decoration:none; }

  .bg-modern-warning{ background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
  .bg-modern-info{ background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%); }
  .bg-modern-success{ background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); }
  .bg-modern-primary{ background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); }

  .card-soft{
    border: 1px solid rgba(148,163,184,.35);
    border-radius: 1rem;
    background: #fff;
    box-shadow: 0 12px 30px rgba(15,23,42,.06);
    overflow:hidden;
  }
  .card-soft .card-header{
    background:#fff;
    border-bottom: 1px solid rgba(148,163,184,.25);
  }
  .table thead th{
    background:#f8fafc;
    border-bottom:1px solid rgba(148,163,184,.35);
    font-size:.85rem;
    color:#0f172a;
    white-space:nowrap;
  }
  .table td{ vertical-align:middle; }
  .pill{
    display:inline-flex; align-items:center; gap:.35rem;
    border:1px solid rgba(148,163,184,.35);
    border-radius:999px;
    padding:.25rem .55rem;
    background:#fff;
    font-weight:800;
    font-size:.78rem;
    color:#0f172a;
  }
</style>

<div class="dash-wrap">

  {{-- HERO --}}
  <div class="hero">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
      <div>
        <h3 class="hero-title">Dashboard (Técnico)</h3>
        <div class="hero-sub">Resumen rápido de pedidos por estado y ranking de clínicas.</div>
      </div>
      <div class="text-muted small text-right">
        <div class="font-weight-bold text-dark">Actualizado</div>
        <div>{{ now()->format('d/m/Y H:i') }}</div>
      </div>
    </div>
  </div>

  {{-- KPI --}}
  <div class="kpi-grid">

    <div class="kpi-col">
      <div class="kpi-card bg-modern-warning">
        <div class="kpi-inner">
          <div>
            <div class="kpi-label">Pendientes</div>
            <div class="kpi-value">{{ $fmt($pendientes) }}</div>
            <div class="small opacity-75 mt-1">Requieren atención</div>
          </div>
          <div class="kpi-icon"><i class="fas fa-hourglass-half"></i></div>
        </div>
        <a href="{{ route('admin.tecnico.pedidos.index', ['estado' => 'pendiente']) }}" class="kpi-link">
          Ver <i class="fas fa-arrow-circle-right"></i>
        </a>
      </div>
    </div>

    <div class="kpi-col">
      <div class="kpi-card bg-modern-info">
        <div class="kpi-inner">
          <div>
            <div class="kpi-label">En proceso</div>
            <div class="kpi-value">{{ $fmt($enProceso) }}</div>
            <div class="small opacity-75 mt-1">Trabajándose</div>
          </div>
          <div class="kpi-icon"><i class="fas fa-cogs"></i></div>
        </div>
        <a href="{{ route('admin.tecnico.pedidos.index', ['estado' => 'en_proceso']) }}" class="kpi-link">
          Ver <i class="fas fa-arrow-circle-right"></i>
        </a>
      </div>
    </div>

    <div class="kpi-col">
      <div class="kpi-card bg-modern-success">
        <div class="kpi-inner">
          <div>
            <div class="kpi-label">Realizados</div>
            <div class="kpi-value">{{ $fmt($realizados) }}</div>
            <div class="small opacity-75 mt-1">Listos para entregar</div>
          </div>
          <div class="kpi-icon"><i class="fas fa-check-circle"></i></div>
        </div>
        <a href="{{ route('admin.tecnico.pedidos.index', ['estado' => 'realizado']) }}" class="kpi-link">
          Ver <i class="fas fa-arrow-circle-right"></i>
        </a>
      </div>
    </div>

    <div class="kpi-col">
      <div class="kpi-card bg-modern-primary">
        <div class="kpi-inner">
          <div>
            <div class="kpi-label">Total</div>
            <div class="kpi-value">{{ $fmt($total) }}</div>
            <div class="small opacity-75 mt-1">Todos los pedidos</div>
          </div>
          <div class="kpi-icon"><i class="fas fa-layer-group"></i></div>
        </div>
        <a href="{{ route('admin.tecnico.pedidos.index') }}" class="kpi-link">
          Ver <i class="fas fa-arrow-circle-right"></i>
        </a>
      </div>
    </div>

  </div>

  {{-- RANKING --}}
  <div class="card card-soft">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h3 class="card-title mb-0">
        <i class="fas fa-chart-bar mr-1"></i>
        Ranking: Clínicas con más pedidos
      </h3>
      <span class="pill">
        <i class="fas fa-trophy"></i> Top {{ is_countable($topClinicas) ? count($topClinicas) : 0 }}
      </span>
    </div>

    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
          <thead>
            <tr>
              <th style="width:60px;">#</th>
              <th>Clínica</th>
              <th class="text-right">Pedidos</th>
            </tr>
          </thead>
          <tbody>
            @forelse($topClinicas as $i => $row)
              <tr>
                <td class="text-muted">{{ $i + 1 }}</td>
                <td class="font-weight-bold">{{ $row['clinica'] }}</td>
                <td class="text-right">
                  <span class="badge badge-dark">{{ $row['total'] }}</span>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="3" class="text-center text-muted py-3">Sin datos.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>
@endsection

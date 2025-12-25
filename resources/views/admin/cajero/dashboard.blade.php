@extends('layouts.admin')

@section('title', 'Dashboard (Cajero)')
@section('content_header', 'Dashboard (Cajero)')

@section('content')
@php
  $gs = fn($n) => number_format((int)$n, 0, ',', '.').' Gs';
@endphp

<style>
  .kpi-card{
    border: 1px solid rgba(148,163,184,.35);
    border-radius: 14px;
    background: #fff;
    box-shadow: 0 10px 25px rgba(15,23,42,.06);
    overflow: hidden;
  }
  .kpi-top{
    display:flex; align-items:center; justify-content:space-between;
    padding: 14px 16px;
  }
  .kpi-title{ font-size:.85rem; color:#64748b; margin:0; }
  .kpi-value{ font-size:1.45rem; font-weight:800; margin:0; letter-spacing:.2px; }
  .kpi-icon{
    width:40px; height:40px; border-radius:12px;
    display:flex; align-items:center; justify-content:center;
    background: rgba(2,132,199,.10);
    color:#0284c7;
  }
  .kpi-footer{
    padding: 10px 16px;
    border-top: 1px solid rgba(148,163,184,.25);
    background: #f8fafc;
    font-size:.85rem;
    color:#475569;
    display:flex; justify-content:space-between; align-items:center;
  }

  .card-soft{
    border: 1px solid rgba(148,163,184,.35);
    border-radius: 14px;
    background: #fff;
    box-shadow: 0 10px 25px rgba(15,23,42,.06);
  }
  .card-soft .card-header{
    background: #fff;
    border-bottom: 1px solid rgba(148,163,184,.25);
    border-top-left-radius: 14px;
    border-top-right-radius: 14px;
  }

  .pill{
    display:inline-flex; align-items:center; gap:.35rem;
    padding:.25rem .6rem; border-radius:999px;
    border:1px solid rgba(148,163,184,.35);
    background:#f8fafc;
    font-size:.80rem; color:#334155;
    white-space:nowrap;
  }
  .table thead th{
    background:#f8fafc;
    border-bottom:1px solid rgba(148,163,184,.35);
    font-size:.85rem;
  }
  .nowrap{ white-space:nowrap; }
  .money{ font-variant-numeric: tabular-nums; }
</style>

{{-- Acciones rápidas --}}
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
  <div class="text-muted small">
    @if($clinicaId)
      Vista filtrada por clínica.
    @else
      Vista global (cajero).
    @endif
  </div>

  <div class="d-flex flex-wrap" style="gap:.5rem;">
    @can('pagos.create')
      <a href="{{ route('admin.pagos.multiple.create') }}" class="btn btn-success btn-sm">
        <i class="fas fa-plus mr-1"></i> Cobro múltiple
      </a>
    @endcan

    @can('estado_cuenta.view')
      <a href="{{ route('admin.estado_cuenta.index') }}" class="btn btn-outline-primary btn-sm">
        <i class="fas fa-file-invoice-dollar mr-1"></i> Estado de cuenta
      </a>
    @endcan

    @can('pedidos.view')
      <a href="{{ route('admin.pedidos.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-clipboard-list mr-1"></i> Pedidos
      </a>
    @endcan
  </div>
</div>

{{-- KPIs --}}
<div class="row">
  <div class="col-lg-3 col-md-6 mb-3">
    <div class="kpi-card">
      <div class="kpi-top">
        <div>
          <p class="kpi-title">Pagado hoy</p>
          <p class="kpi-value money">{{ $gs($pagadoHoy ?? 0) }}</p>
        </div>
        <div class="kpi-icon" style="background: rgba(16,185,129,.12); color:#10b981;">
          <i class="fas fa-cash-register"></i>
        </div>
      </div>
      <div class="kpi-footer">
        <span class="pill"><i class="fas fa-calendar-day"></i> Hoy</span>
        <span class="text-muted">Ingresos</span>
      </div>
    </div>
  </div>

{{-- 
  <div class="col-lg-3 col-md-6 mb-3">
    <div class="kpi-card">
      <div class="kpi-top">
        <div>
          <p class="kpi-title">Pagado mes</p>
          <p class="kpi-value money">{{ $gs($pagadoMes ?? 0) }}</p>
        </div>
        <div class="kpi-icon" style="background: rgba(59,130,246,.12); color:#3b82f6;">
          <i class="fas fa-calendar-alt"></i>
        </div>
      </div>
      <div class="kpi-footer">
        <span class="pill"><i class="fas fa-calendar"></i> Mes</span>
        <span class="text-muted">Acumulado</span>
      </div>
    </div>
  </div>
--}}

  <div class="col-lg-3 col-md-6 mb-3">
    <div class="kpi-card">
      <div class="kpi-top">
        <div>
          <p class="kpi-title">Pendiente total</p>
          <p class="kpi-value money" style="color:#ef4444;">{{ $gs($pendientePagoTotal ?? 0) }}</p>
        </div>
        <div class="kpi-icon" style="background: rgba(239,68,68,.12); color:#ef4444;">
          <i class="fas fa-exclamation-circle"></i>
        </div>
      </div>
      <div class="kpi-footer">
        <span class="pill"><i class="fas fa-file-invoice"></i> Liquidaciones</span>
        <span class="text-muted">{{ number_format($liqPendientesCount ?? 0, 0, ',', '.') }} pendientes</span>
      </div>
    </div>
  </div>

  <div class="col-lg-3 col-md-6 mb-3">
    <div class="kpi-card">
      <div class="kpi-top">
        <div>
          <p class="kpi-title">Pagos a cuenta</p>
          <p class="kpi-value money">{{ $gs($pagosACuentaTotal ?? 0) }}</p>
        </div>
        <div class="kpi-icon" style="background: rgba(245,158,11,.12); color:#f59e0b;">
          <i class="fas fa-wallet"></i>
        </div>
      </div>
      <div class="kpi-footer">
        <span class="pill"><i class="fas fa-receipt"></i> Saldo a favor</span>
        <span class="text-muted">Disponible</span>
      </div>
    </div>
  </div>

  {{-- ✅ NUEVO KPI: pedidos pendientes de liquidar --}}
  <div class="col-lg-3 col-md-6 mb-3">
    <div class="kpi-card">
      <div class="kpi-top">
        <div>
          <p class="kpi-title">Pedidos por liquidar</p>
          <p class="kpi-value money" style="color:#f59e0b;">
            {{ number_format($pedidosPendientesLiquidacionCount ?? 0, 0, ',', '.') }}
          </p>
        </div>
        <div class="kpi-icon" style="background: rgba(245,158,11,.12); color:#f59e0b;">
          <i class="fas fa-file-signature"></i>
        </div>
      </div>
      <div class="kpi-footer">
        <span class="pill"><i class="fas fa-clipboard-check"></i> Finalizados</span>
        <span class="text-muted">Pendientes de liquidación</span>
      </div>
    </div>
  </div>
</div>

<div class="row">
  {{-- Gráfico pagos 14 días --}}
  <div class="col-lg-8 mb-3">
    <div class="card card-soft">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div>
          <strong>Pagos (últimos 14 días)</strong>
          <div class="text-muted small">Suma diaria de cobros</div>
        </div>
        <span class="pill"><i class="fas fa-chart-line"></i> Tendencia</span>
      </div>
      <div class="card-body">
        <div style="height: 260px;">
          <canvas id="chartPagos14"></canvas>
        </div>
      </div>
    </div>
  </div>

  {{-- Top pendiente por clínica --}}
  <div class="col-lg-4 mb-3">
    <div class="card card-soft">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div>
          <strong>Pendiente por clínica</strong>
          <div class="text-muted small">Top 6 (saldo pendiente)</div>
        </div>
        <span class="pill"><i class="fas fa-hospital"></i> Ranking</span>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-hover mb-0">
            <thead>
              <tr>
                <th>Clínica</th>
                <th class="text-right">Pendiente</th>
              </tr>
            </thead>
            <tbody>
              @forelse($topPendienteClinicas as $r)
                <tr>
                  <td class="nowrap">{{ $r['clinica'] }}</td>
                  <td class="text-right money" style="color:#ef4444;">
                    {{ $gs($r['pendiente']) }}
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="2" class="text-center text-muted py-3">Sin datos.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- ✅ NUEVO: Pedidos pendientes de liquidar --}}
<div class="card card-soft mb-3">
  <div class="card-header d-flex justify-content-between align-items-center">
    <div>
      <strong>Pedidos pendientes de liquidar</strong>
      <div class="text-muted small">
        Pedidos finalizados que aún no tienen liquidación confirmada.
      </div>
    </div>
    <span class="pill">
      <i class="fas fa-file-signature"></i>
      {{ number_format($pedidosPendientesLiquidacionCount ?? 0, 0, ',', '.') }} pendientes
    </span>
  </div>

  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover table-sm mb-0">
        <thead>
          <tr>
            <th style="width:90px;">#</th>
            <th>Código</th>
            <th>Clínica</th>
            <th>Paciente</th>
            <th class="text-right">Fecha</th>
            <th class="text-center" style="width:170px;">Acciones</th>
          </tr>
        </thead>
        <tbody>
          @forelse($pedidosPendientesLiquidacion as $p)
            <tr>
              <td class="text-muted">{{ $p->id }}</td>
              <td class="nowrap">
                <strong>{{ $p->codigo_pedido ?? $p->codigo ?? ('PED-'.$p->id) }}</strong>
              </td>
              <td class="nowrap">{{ optional($p->clinica)->nombre ?? '-' }}</td>
              <td class="nowrap">
                {{ trim((optional($p->paciente)->apellido ?? '').' '.(optional($p->paciente)->nombre ?? '')) ?: '-' }}
              </td>
              <td class="text-right nowrap text-muted">
                {{ optional($p->created_at)->format('d/m/Y H:i') ?? '-' }}
              </td>
              <td class="text-center nowrap">
                @can('pedidos.view')
                  <a class="btn btn-sm btn-outline-secondary"
                     href="{{ route('admin.pedidos.show', $p) }}">
                    <i class="fas fa-eye mr-1"></i> Ver
                  </a>
                @endcan

                {{-- Liquidar: ruta existente en tus routes --}}
                @can('pagos.create')
                  <a class="btn btn-sm btn-warning"
                     href="{{ route('admin.pedidos.liquidar', $p) }}">
                    <i class="fas fa-file-invoice-dollar mr-1"></i> Liquidar
                  </a>
                @endcan
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center text-muted py-4">
                No hay pedidos pendientes de liquidación.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

{{-- Últimos pagos --}}
<div class="card card-soft">
  <div class="card-header d-flex justify-content-between align-items-center">
    <div>
      <strong>Últimos pagos</strong>
      <div class="text-muted small">Cobros recientes</div>
    </div>
    <span class="pill"><i class="fas fa-history"></i> Recientes</span>
  </div>

  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover table-sm mb-0">
        <thead>
          <tr>
            <th style="width:80px;">#</th>
            <th>Clínica</th>
            <th>Usuario</th>
            <th class="text-right">Monto</th>
            <th class="text-right">Fecha</th>
            <th class="text-center" style="width:140px;">Acciones</th>
          </tr>
        </thead>
        <tbody>
          @forelse($ultimosPagos as $p)
            <tr>
              <td class="text-muted">{{ $p->id }}</td>
              <td class="nowrap">{{ optional($p->clinica)->nombre ?? '-' }}</td>
              <td class="nowrap">{{ optional($p->user)->name ?? '-' }}</td>
              <td class="text-right money">{{ number_format((int)($p->monto_gs ?? $p->total_gs ?? 0), 0, ',', '.') }} Gs</td>
              <td class="text-right nowrap text-muted">
                {{ optional($p->fecha ?? $p->created_at)->format('d/m/Y H:i') }}
              </td>
              <td class="text-center nowrap">
                @can('pagos.view')
                  <a class="btn btn-sm btn-outline-primary"
                     href="{{ route('admin.pagos.show', $p) }}">
                    <i class="fas fa-eye mr-1"></i> Ver
                  </a>
                @endcan
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center text-muted py-4">Sin pagos aún.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection

@push('scripts')
  {{-- Chart.js CDN --}}
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

  <script>
    (function () {
      if (typeof Chart === 'undefined') return;

      const labels = @json($pagosPorDiaLabels ?? []);
      const data   = @json($pagosPorDiaData ?? []);

      const el = document.getElementById('chartPagos14');
      if (!el) return;

      new Chart(el, {
        type: 'line',
        data: {
          labels,
          datasets: [{
            label: 'Pagos (Gs)',
            data,
            tension: 0.35,
            fill: true,
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: true } },
          scales: { y: { beginAtZero: true } }
        }
      });
    })();
  </script>
@endpush

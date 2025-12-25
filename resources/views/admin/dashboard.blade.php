 {{-- resources/views/admin/dashboard.blade.php --}}
@extends('layouts.admin')

@section('title', 'Dashboard')
@section('content_header', 'Dashboard')

@section('content')
@php
  $fmt = fn($n) => number_format((int)($n ?? 0), 0, ',', '.');

  $badgeEstado = function ($estado) {
      $estado = (string)($estado ?? '');
      return match ($estado) {
          'pendiente'  => 'badge badge-warning',
          'en_proceso' => 'badge badge-info',
          'finalizado', 'terminado' => 'badge badge-success',
          'cancelado'  => 'badge badge-danger',
          default      => 'badge badge-secondary',
      };
  };
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
  .hero-title{ font-size: 1.15rem; font-weight: 950; margin:0; color:#0f172a; }
  .hero-sub{ margin-top:.35rem; color:#64748b; font-size:.9rem; }

  .kpi-grid{ display:grid; grid-template-columns:repeat(12, 1fr); gap:.9rem; }
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
  .kpi-label{ font-size:.85rem; font-weight:900; opacity:.92; letter-spacing:.2px; }
  .kpi-value{ font-size:1.65rem; font-weight: 950; margin-top:.15rem; }
  .kpi-sub{ font-size:.82rem; opacity:.82; margin-top:.25rem; }
  .kpi-icon{
    width:46px; height:46px; border-radius:.9rem;
    display:grid; place-items:center;
    background: rgba(255,255,255,.16);
    border: 1px solid rgba(255,255,255,.20);
    flex: 0 0 auto;
  }
  .kpi-icon i{ font-size:1.25rem; }

  .bg-kpi-info{ background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%); }
  .bg-kpi-success{ background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); }
  .bg-kpi-warning{ background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
  .bg-kpi-primary{ background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); }
  .bg-kpi-secondary{ background: linear-gradient(135deg, #64748b 0%, #475569 100%); }
  .bg-kpi-dark{ background: linear-gradient(135deg, #111827 0%, #0b1220 100%); }
  .bg-kpi-teal{ background: linear-gradient(135deg, #14b8a6 0%, #0f766e 100%); }

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
  .card-title{ font-weight: 900; }
  .table thead th{
    background:#f8fafc;
    border-bottom:1px solid rgba(148,163,184,.35);
    font-size:.85rem;
    color:#0f172a;
    white-space:nowrap;
  }
  .table td{ vertical-align:middle; }

  .chart-box{
    border: 1px solid rgba(148,163,184,.25);
    border-radius: .9rem;
    background: #fff;
    padding: .75rem;
  }
  .pill{
    display:inline-flex; align-items:center; gap:.35rem;
    border:1px solid rgba(148,163,184,.35);
    border-radius:999px;
    padding:.25rem .55rem;
    background:#fff;
    font-weight:900;
    font-size:.78rem;
    color:#0f172a;
    white-space:nowrap;
  }
</style>

<div class="dash-wrap">

  {{-- HERO --}}
  <div class="hero">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
      <div>
        <h3 class="hero-title">Dashboard</h3>
        <div class="hero-sub">Indicadores clave, pagos y actividad reciente del sistema.</div>
      </div>
      <div class="text-muted small text-right">
        <div class="font-weight-bold text-dark">Actualizado</div>
        <div>{{ now()->format('d/m/Y H:i') }}</div>
      </div>
    </div>
  </div>

  {{-- KPIs (modernos) --}}
  <div class="kpi-grid">

    <div class="kpi-col">
      <div class="kpi-card bg-kpi-info">
        <div class="kpi-inner">
          <div>
            <div class="kpi-label">Pedidos (total)</div>
            <div class="kpi-value">{{ $fmt($totalPedidos) }}</div>
            <div class="kpi-sub">Acumulado histórico</div>
          </div>
          <div class="kpi-icon"><i class="fas fa-clipboard-list"></i></div>
        </div>
      </div>
    </div>

    <div class="kpi-col">
      <div class="kpi-card bg-kpi-success">
        <div class="kpi-inner">
          <div>
            <div class="kpi-label">Pedidos hoy</div>
            <div class="kpi-value">{{ $fmt($pedidosHoy) }}</div>
            <div class="kpi-sub">Últimas 24 horas</div>
          </div>
          <div class="kpi-icon"><i class="fas fa-calendar-day"></i></div>
        </div>
      </div>
    </div>

    <div class="kpi-col">
      <div class="kpi-card bg-kpi-warning">
        <div class="kpi-inner">
          <div>
            <div class="kpi-label">Últimos 7 días</div>
            <div class="kpi-value">{{ $fmt($pedidosSemana) }}</div>
            <div class="kpi-sub">Actividad semanal</div>
          </div>
          <div class="kpi-icon"><i class="fas fa-calendar-week"></i></div>
        </div>
      </div>
    </div>

    <div class="kpi-col">
      <div class="kpi-card bg-kpi-primary">
        <div class="kpi-inner">
          <div>
            <div class="kpi-label">Últimos 30 días</div>
            <div class="kpi-value">{{ $fmt($pedidosMes) }}</div>
            <div class="kpi-sub">Tendencia mensual</div>
          </div>
          <div class="kpi-icon"><i class="fas fa-chart-line"></i></div>
        </div>
      </div>
    </div>

    <div class="kpi-col">
      <div class="kpi-card bg-kpi-secondary">
        <div class="kpi-inner">
          <div>
            <div class="kpi-label">Pacientes</div>
            <div class="kpi-value">{{ $fmt($totalPacientes) }}</div>
            <div class="kpi-sub">Total registrados</div>
          </div>
          <div class="kpi-icon"><i class="fas fa-user-injured"></i></div>
        </div>
      </div>
    </div>

    {{-- Pagos --}}
    <div class="kpi-col">
      <div class="kpi-card bg-kpi-success">
        <div class="kpi-inner">
          <div>
            <div class="kpi-label">Pagado hoy</div>
            <div class="kpi-value">{{ $fmt($pagadoHoy) }}</div>
            <div class="kpi-sub">Ingresos del día</div>
          </div>
          <div class="kpi-icon"><i class="fas fa-cash-register"></i></div>
        </div>
      </div>
    </div>

    <div class="kpi-col">
      <div class="kpi-card bg-kpi-primary">
        <div class="kpi-inner">
          <div>
            <div class="kpi-label">Pagado mes</div>
            <div class="kpi-value">{{ $fmt($pagadoMes) }}</div>
            <div class="kpi-sub">Acumulado mensual</div>
          </div>
          <div class="kpi-icon"><i class="fas fa-calendar-alt"></i></div>
        </div>
      </div>
    </div>

    <div class="kpi-col">
      <div class="kpi-card bg-kpi-warning">
        <div class="kpi-inner">
          <div>
            <div class="kpi-label">Pendiente de pago</div>
            <div class="kpi-value">{{ $fmt($pendientePagoTotal) }}</div>
            <div class="kpi-sub">Saldo por cobrar</div>
          </div>
          <div class="kpi-icon"><i class="fas fa-exclamation-circle"></i></div>
        </div>
      </div>
    </div>

    @if(!is_null($totalClinicas))
      <div class="kpi-col">
        <div class="kpi-card bg-kpi-dark">
          <div class="kpi-inner">
            <div>
              <div class="kpi-label">Clínicas</div>
              <div class="kpi-value">{{ $fmt($totalClinicas) }}</div>
              <div class="kpi-sub">Activas en el sistema</div>
            </div>
            <div class="kpi-icon"><i class="fas fa-hospital"></i></div>
          </div>
        </div>
      </div>
    @endif

    @if(!is_null($totalUsuarios))
      <div class="kpi-col">
        <div class="kpi-card bg-kpi-teal">
          <div class="kpi-inner">
            <div>
              <div class="kpi-label">Usuarios</div>
              <div class="kpi-value">{{ $fmt($totalUsuarios) }}</div>
              <div class="kpi-sub">Total registrados</div>
            </div>
            <div class="kpi-icon"><i class="fas fa-users"></i></div>
          </div>
        </div>
      </div>
    @endif

  </div>

  {{-- Gráficos --}}
  <div class="row">
    <div class="col-lg-8">
      <div class="card card-soft">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h3 class="card-title mb-0">Pedidos (últimos 14 días)</h3>
          <span class="pill"><i class="fas fa-chart-area"></i> Tendencia</span>
        </div>
        <div class="card-body">
          <div class="chart-box">
            <canvas id="chartPedidosPorDia" height="90"></canvas>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card card-soft">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h3 class="card-title mb-0">Distribución por estado</h3>
          <span class="pill"><i class="fas fa-chart-pie"></i> Estados</span>
        </div>
        <div class="card-body">
          <div class="chart-box">
            <canvas id="chartEstados" height="180"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Gráficos pagos --}}
  <div class="row">
    <div class="col-lg-8">
      <div class="card card-soft">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h3 class="card-title mb-0">Pagos (últimos 14 días)</h3>
          <span class="pill"><i class="fas fa-hand-holding-usd"></i> Ingresos</span>
        </div>
        <div class="card-body">
          <div class="chart-box">
            <canvas id="chartPagosPorDia" height="90"></canvas>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card card-soft">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h3 class="card-title mb-0">Pagos por mes (últimos 12)</h3>
          <span class="pill"><i class="fas fa-calendar"></i> 12 meses</span>
        </div>
        <div class="card-body">
          <div class="chart-box">
            <canvas id="chartPagosPorMes" height="180"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Últimos pedidos + Actividad --}}
  <div class="row">
    <div class="col-lg-8">
      <div class="card card-soft">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h3 class="card-title mb-0">Últimos pedidos</h3>
          <span class="pill"><i class="fas fa-clock"></i> Recientes</span>
        </div>

        <div class="card-body p-0 table-responsive">
          <table class="table table-hover mb-0">
            <thead>
              <tr>
                <th>#</th>
                <th>Código</th>
                <th>Clínica</th>
                <th>Paciente</th>
                <th>Estado</th>
                <th class="text-nowrap">Fecha</th>
              </tr>
            </thead>
            <tbody>
              @forelse($ultimosPedidos as $p)
                @php
                  $estado = $p->estado ?? 'sin_estado';
                @endphp
                <tr>
                  <td class="text-muted">{{ $p->id }}</td>
                  <td class="text-nowrap font-weight-bold">
                    {{ $p->codigo_pedido ?? $p->codigo ?? ('PED-' . $p->id) }}
                  </td>
                  <td>{{ optional($p->clinica)->nombre ?? '-' }}</td>
                  <td>{{ optional($p->paciente)->nombre ?? '-' }}</td>
                  <td>
                    <span class="{{ $badgeEstado($estado) }}">
                      {{ \Illuminate\Support\Str::headline($estado) }}
                    </span>
                  </td>
                  <td class="text-nowrap text-muted">{{ optional($p->created_at)->format('d/m/Y H:i') }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="6" class="text-center text-muted p-4">Sin pedidos aún.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

      </div>
    </div>

    <div class="col-lg-4">
      <div class="card card-soft">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h3 class="card-title mb-0">Actividad reciente</h3>
          <span class="pill"><i class="fas fa-history"></i> Logs</span>
        </div>
        <div class="card-body">
          @forelse($actividad as $a)
            <div class="d-flex mb-3">
              <div class="mr-2 text-muted">
                <i class="fas fa-history"></i>
              </div>
              <div class="flex-grow-1">
                <div class="small text-muted">
                  {{ optional($a->created_at)->format('d/m/Y H:i') }}
                </div>
                <div>
                  <strong>{{ $a->log_name ?? 'sistema' }}</strong> —
                  {{ $a->description ?? 'evento' }}
                </div>
                <div class="small text-muted">
                  Actor: {{ optional($a->causer)->name ?? 'Sistema' }}
                </div>
              </div>
            </div>
            <hr class="my-2">
          @empty
            <div class="text-muted">Sin actividad registrada.</div>
          @endforelse
        </div>
      </div>
    </div>
  </div>

</div>
@endsection

@push('js')
  {{-- Chart.js (ruta típica de AdminLTE) --}}
  <script src="{{ asset('vendor/adminlte/plugins/chart.js/Chart.min.js') }}"></script>

  <script>
    (function () {
      // Pedidos por día (línea)
      const labelsDia = @json($pedidosPorDiaLabels ?? []);
      const dataDia   = @json($pedidosPorDiaData ?? []);

      const ctx1 = document.getElementById('chartPedidosPorDia');
      if (ctx1) {
        new Chart(ctx1, {
          type: 'line',
          data: {
            labels: labelsDia,
            datasets: [{
              label: 'Pedidos',
              data: dataDia,
              tension: 0.35
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: true } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
          }
        });
      }

      // Estados (dona)
      const labelsEstado = @json($estadoLabels ?? []);
      const dataEstado   = @json($estadoData ?? []);

      const ctx2 = document.getElementById('chartEstados');
      if (ctx2) {
        new Chart(ctx2, {
          type: 'doughnut',
          data: {
            labels: labelsEstado,
            datasets: [{ data: dataEstado }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } }
          }
        });
      }

      // Pagos por día (línea)
      const labelsPagosDia = @json($pagosPorDiaLabels ?? []);
      const dataPagosDia   = @json($pagosPorDiaData ?? []);

      const ctx3 = document.getElementById('chartPagosPorDia');
      if (ctx3) {
        new Chart(ctx3, {
          type: 'line',
          data: {
            labels: labelsPagosDia,
            datasets: [{
              label: 'Pagado',
              data: dataPagosDia,
              tension: 0.35
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: true } },
            scales: { y: { beginAtZero: true } }
          }
        });
      }

      // Pagos por mes (barras)
      const labelsPagosMes = @json($pagosPorMesLabels ?? []);
      const dataPagosMes   = @json($pagosPorMesData ?? []);

      const ctx4 = document.getElementById('chartPagosPorMes');
      if (ctx4) {
        new Chart(ctx4, {
          type: 'bar',
          data: {
            labels: labelsPagosMes,
            datasets: [{
              label: 'Pagado',
              data: dataPagosMes
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: true } },
            scales: { y: { beginAtZero: true } }
          }
        });
      }
    })();
  </script>
@endpush

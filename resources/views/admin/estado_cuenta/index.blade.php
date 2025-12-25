@extends('layouts.admin')

@section('title', 'Estado de cuenta')
@section('content_header', 'Estado de cuenta por Clínica')

@section('content')
@php
  $fmt = fn($n) => number_format((int)$n, 0, ',', '.');

  $user = auth()->user();
  $isAdmin  = $user?->hasRole('admin') ?? false;
  $isCajero = $user?->hasRole('cajero') ?? false;
  $isClinica = $user?->hasRole('clinica') ?? false;

  // ✅ Solo admin/cajero pueden seleccionar otra clínica o "Todas"
  $canChooseClinica = $isAdmin || $isCajero;

  // ✅ Blindaje: si es clínica, forzamos el query base con su clinica_id (para tabs/links)
  $forcedClinicaId = (int)($user->clinica_id ?? 0);
  $qBase = request()->except('page');

  if ($isClinica && $forcedClinicaId > 0) {
      $qBase['clinica_id'] = $forcedClinicaId;   // pisa cualquier clinica_id que venga por URL
  }

  // URLs tabs (siempre conservan filtros)
  $urlPendientes = route('admin.estado_cuenta.index', array_merge($qBase, ['tab' => 'pendientes']));
  $urlPagados    = route('admin.estado_cuenta.index', array_merge($qBase, ['tab' => 'pagados']));
  $urlPagos      = route('admin.estado_cuenta.index', array_merge($qBase, ['tab' => 'pagos']));

  $verPagosUrlTop = $urlPagos;

  // Para mostrar el nombre de la clínica cuando es rol clinica
  $clinicaNombreSolo = $isClinica ? ($clinicas->first()?->nombre ?? '—') : null;
@endphp

<style>
  .kpi-card{
    border: 1px solid rgba(148,163,184,.35);
    border-radius: .85rem;
    background: #fff;
    box-shadow: 0 10px 25px rgba(15,23,42,.06);
  }
  .kpi-card .label{ font-size:.78rem; color:#6b7280; }
  .kpi-card .value{ font-size:1.35rem; font-weight:800; margin: .15rem 0 0; letter-spacing:.2px; }
  .kpi-card .unit{ font-size:.85rem; color:#6b7280; font-weight:600; margin-left:.25rem; }

  .card-soft{
    border: 1px solid rgba(148,163,184,.35);
    border-radius: .85rem;
    background: #fff;
    box-shadow: 0 10px 25px rgba(15,23,42,.06);
  }
  .filters .form-control{ border-radius: .65rem; }
  .btn-round{ border-radius: .65rem; }
  .nav-pills .nav-link{
    border-radius: .65rem;
    font-weight: 600;
    padding: .45rem .85rem;
  }

  .table thead th{
    font-size: .85rem;
    color: #111827;
    background: #f8fafc;
    border-bottom: 1px solid rgba(148,163,184,.35);
  }
  .table td{ vertical-align: middle; }
  .money{ font-variant-numeric: tabular-nums; font-weight: 700; }
  .muted{ color:#6b7280; }
  .actions .btn{ margin-left: .35rem; }
</style>

{{-- FILTROS --}}
<div class="card card-soft mb-3 filters">
  <div class="card-body">
    <form method="get" class="row g-2 align-items-end">
      {{-- ✅ CLÍNICA: NO puede elegir otra clínica --}}
      <div class="col-md-5">
        <label class="small text-muted mb-1">Clínica</label>

        @if($canChooseClinica)
          <select name="clinica_id" class="form-control">
            <option value="0">Todas</option>
            @foreach($clinicas as $c)
              <option value="{{ $c->id }}" @selected((int)$clinicaId === (int)$c->id)>{{ $c->nombre }}</option>
            @endforeach
          </select>
        @else
          {{-- Solo lectura + hidden --}}
          <input type="text" class="form-control" value="{{ $clinicaNombreSolo }}" readonly>
          <input type="hidden" name="clinica_id" value="{{ (int)($user->clinica_id ?? 0) }}">
        @endif
      </div>

      <div class="col-md-2">
        <label class="small text-muted mb-1">Desde</label>
        <input type="date" name="desde" value="{{ $desde }}" class="form-control">
      </div>

      <div class="col-md-2">
        <label class="small text-muted mb-1">Hasta</label>
        <input type="date" name="hasta" value="{{ $hasta }}" class="form-control">
      </div>

      <div class="col-md-3 d-flex gap-2">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <button class="btn btn-primary btn-round w-100">
          <i class="fas fa-search mr-1"></i> Filtrar
        </button>
        <a class="btn btn-secondary btn-round"
           href="{{ route('admin.estado_cuenta.index', $isClinica ? ['clinica_id' => (int)($user->clinica_id ?? 0)] : []) }}">
          Limpiar
        </a>
      </div>

      {{-- Botón superior Ver pagos (mantiene filtros) --}}
      <div class="col-12 d-flex justify-content-end mt-2">
        @canany(['pagos.view','pagos.show'])
          <a href="{{ $verPagosUrlTop }}" class="btn btn-outline-primary btn-round">
            <i class="fas fa-receipt mr-1"></i> Ver pagos
          </a>
        @endcanany
      </div>
    </form>
  </div>
</div>

{{-- KPI / RESUMEN --}}
<div class="row g-3">
  <div class="col-md-3">
    <div class="kpi-card p-3">
      <div class="label">Total liquidado</div>
      <div class="value">{{ $fmt($totalLiquidado) }}<span class="unit">Gs</span></div>
    </div>
  </div>

  <div class="col-md-3">
    <div class="kpi-card p-3">
      <div class="label">Total pagado</div>
      <div class="value">{{ $fmt($totalPagado) }}<span class="unit">Gs</span></div>
    </div>
  </div>

  <div class="col-md-3">
    <div class="kpi-card p-3" style="border-color: rgba(239,68,68,.35);">
      <div class="label">Saldo pendiente</div>
      <div class="value text-danger">{{ $fmt($saldoPendiente) }}<span class="unit">Gs</span></div>
    </div>
  </div>

  <div class="col-md-3">
    <div class="kpi-card p-3" style="border-color: rgba(34,197,94,.35);">
      <div class="label">Pagos a cuenta</div>
      <div class="value text-success">{{ $fmt($pagosACuenta) }}<span class="unit">Gs</span></div>
    </div>
  </div>
</div>

{{-- TABS + TABLAS --}}
<div class="card card-soft mt-3">
  <div class="card-header bg-white">
    <ul class="nav nav-pills card-header-pills">
      <li class="nav-item">
        <a class="nav-link {{ $tab==='pendientes' ? 'active' : '' }}" href="{{ $urlPendientes }}">
          Pendientes
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link {{ $tab==='pagados' ? 'active' : '' }}" href="{{ $urlPagados }}">
          Pagados
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link {{ $tab==='pagos' ? 'active' : '' }}" href="{{ $urlPagos }}">
          Pagos
        </a>
      </li>
    </ul>
  </div>

  <div class="card-body p-0">
    @if($tab === 'pagos')
      {{-- TAB PAGOS --}}
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th style="width:90px;">#</th>
              <th>Fecha</th>
              <th class="text-right">Total</th>
              <th class="text-right">Aplicado</th>
              <th class="text-right">Saldo a favor</th>
              <th class="text-right" style="width: 220px;">Acciones</th>
            </tr>
          </thead>
          <tbody>
            @forelse($pagos as $p)
              <tr>
                <td class="fw-bold">#{{ $p->id }}</td>
                <td class="muted">{{ optional($p->created_at)->format('d/m/Y H:i') }}</td>
                <td class="text-right money">{{ $fmt($p->total_gs) }}</td>
                <td class="text-right money">{{ $fmt($p->aplicado_gs) }}</td>
                <td class="text-right money {{ (int)$p->saldo_a_favor_gs > 0 ? 'text-success' : 'muted' }}">
                  {{ $fmt($p->saldo_a_favor_gs) }}
                </td>
                <td class="text-right actions">
                  @can('pagos.show')
                    <a class="btn btn-sm btn-outline-primary btn-round"
                       href="{{ route('admin.pagos.show', $p) }}">
                      <i class="fas fa-eye mr-1"></i> Ver
                    </a>
                  @endcan

                  @can('pagos.pdf')
                    <a class="btn btn-sm btn-outline-secondary btn-round"
                       target="_blank"
                       href="{{ route('admin.pagos.pdf', $p) }}">
                      <i class="fas fa-file-pdf mr-1"></i> PDF
                    </a>
                  @endcan
                </td>
              </tr>
            @empty
              <tr><td colspan="6" class="text-center text-muted p-4">Sin pagos.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="p-3">
        {{ $pagos->links() }}
      </div>
    @else
      {{-- TAB PENDIENTES / PAGADOS --}}
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th style="width:120px;">Liquidación</th>
              <th>Pedido</th>
              <th style="width:170px;">Fecha</th>
              <th class="text-right">Total</th>
              <th class="text-right">Pagado</th>
              <th class="text-right">Saldo</th>
              <th class="text-right" style="width: 320px;">Acciones</th>
            </tr>
          </thead>
          <tbody>
            @forelse($liquidaciones as $l)
              @php
                $verPagosPorFila = route('admin.estado_cuenta.index', array_merge($qBase, [
                  'tab' => 'pagos',
                  'liquidacion_id' => $l->id,
                ]));
              @endphp
              <tr>
                <td class="fw-bold">#{{ $l->id }}</td>
                <td>
                  @can('pedidos.show')
                    <a href="{{ route('admin.pedidos.show', $l->pedido_id) }}" class="fw-semibold">
                      Pedido #{{ $l->pedido_id }}
                    </a>
                  @else
                    <span class="fw-semibold">Pedido #{{ $l->pedido_id }}</span>
                  @endcan
                </td>
                <td class="muted">{{ optional($l->liquidado_at)->format('d/m/Y H:i') }}</td>
                <td class="text-right money">{{ $fmt($l->total_gs) }}</td>
                <td class="text-right money">{{ $fmt($l->aplicado_gs) }}</td>
                <td class="text-right money {{ (int)$l->saldo_gs > 0 ? 'text-danger' : 'text-success' }}">
                  {{ $fmt($l->saldo_gs) }}
                </td>

                <td class="text-right actions">
                  @can('pedidos.liquidar')
                    <a class="btn btn-sm btn-outline-secondary btn-round"
                       href="{{ route('admin.pedidos.liquidar', $l->pedido_id) }}">
                      <i class="fas fa-file-invoice mr-1"></i> Ver liquidación
                    </a>
                  @endcan

                  {{-- ✅ Ver pagos (al lado) --}}
                  @canany(['pagos.view','pagos.show'])
                    <a class="btn btn-sm btn-outline-primary btn-round"
                       href="{{ $verPagosPorFila }}">
                      <i class="fas fa-receipt mr-1"></i> Ver pagos
                    </a>
                  @endcanany
                </td>
              </tr>
            @empty
              <tr><td colspan="7" class="text-center text-muted p-4">Sin registros.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="p-3">
        {{ $liquidaciones->links() }}
      </div>
    @endif
  </div>
</div>
@endsection

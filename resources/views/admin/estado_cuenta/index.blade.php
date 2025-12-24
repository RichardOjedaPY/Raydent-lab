@extends('layouts.admin')

@section('title', 'Estado de cuenta')
@section('content_header', 'Estado de cuenta por Clínica')

@section('content')
@php
  $fmt = fn($n) => number_format((int)$n, 0, ',', '.');
@endphp

<div class="card mb-3">
  <div class="card-body">
    <form method="get" class="row g-2 align-items-end">
      <div class="col-md-5">
        <label class="small text-muted mb-1">Clínica</label>
        <select name="clinica_id" class="form-control">
          <option value="0">Todas</option>
          @foreach($clinicas as $c)
            <option value="{{ $c->id }}" @selected((int)$clinicaId === (int)$c->id)>{{ $c->nombre }}</option>
          @endforeach
        </select>
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
        <button class="btn btn-primary w-100">
          <i class="fas fa-search"></i> Filtrar
        </button>
        <a class="btn btn-secondary" href="{{ route('admin.estado_cuenta.index') }}">
          Limpiar
        </a>
      </div>
    </form>
  </div>
</div>

{{-- Resumen --}}
<div class="row">
  <div class="col-md-3">
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="small text-muted">Total liquidado</div>
        <div class="h4 mb-0">{{ $fmt($totalLiquidado) }} <small class="text-muted">Gs</small></div>
      </div>
    </div>
  </div>

  <div class="col-md-3">
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="small text-muted">Total pagado</div>
        <div class="h4 mb-0">{{ $fmt($totalPagado) }} <small class="text-muted">Gs</small></div>
      </div>
    </div>
  </div>

  <div class="col-md-3">
    <div class="card shadow-sm border-danger">
      <div class="card-body">
        <div class="small text-muted">Saldo pendiente</div>
        <div class="h4 mb-0 text-danger">{{ $fmt($saldoPendiente) }} <small class="text-muted">Gs</small></div>
      </div>
    </div>
  </div>

  <div class="col-md-3">
    <div class="card shadow-sm border-success">
      <div class="card-body">
        <div class="small text-muted">Pagos a cuenta</div>
        <div class="h4 mb-0 text-success">{{ $fmt($pagosACuenta) }} <small class="text-muted">Gs</small></div>
      </div>
    </div>
  </div>
</div>

<div class="card mt-3">
  <div class="card-header">
    <ul class="nav nav-pills card-header-pills">
      <li class="nav-item">
        <a class="nav-link {{ $tab==='pendientes' ? 'active' : '' }}"
           href="{{ route('admin.estado_cuenta.index', request()->except('page') + ['tab'=>'pendientes']) }}">
          Pendientes
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link {{ $tab==='pagados' ? 'active' : '' }}"
           href="{{ route('admin.estado_cuenta.index', request()->except('page') + ['tab'=>'pagados']) }}">
          Pagados
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link {{ $tab==='pagos' ? 'active' : '' }}"
           href="{{ route('admin.estado_cuenta.index', request()->except('page') + ['tab'=>'pagos']) }}">
          Pagos
        </a>
      </li>
    </ul>
  </div>

  <div class="card-body p-0">
    @if($tab === 'pagos')
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th>#</th>
              <th>Fecha</th>
              <th class="text-right">Total</th>
              <th class="text-right">Aplicado</th>
              <th class="text-right">Saldo a favor</th>
              <th class="text-right" style="width: 180px;"></th>
            </tr>
          </thead>
          <tbody>
            @forelse($pagos as $p)
              <tr>
                <td>{{ $p->id }}</td>
                <td>{{ optional($p->created_at)->format('d/m/Y H:i') }}</td>
                <td class="text-right">{{ $fmt($p->total_gs) }}</td>
                <td class="text-right">{{ $fmt($p->aplicado_gs) }}</td>
                <td class="text-right {{ (int)$p->saldo_a_favor_gs > 0 ? 'text-success' : 'text-muted' }}">
                  {{ $fmt($p->saldo_a_favor_gs) }}
                </td>
                <td class="text-right">
                  @can('pagos.show')
                    <a class="btn btn-sm btn-outline-primary"
                       href="{{ route('admin.pagos.show', $p) }}">
                      Ver
                    </a>
                  @endcan

                  @can('pagos.pdf')
                    <a class="btn btn-sm btn-outline-secondary"
                       target="_blank"
                       href="{{ route('admin.pagos.pdf', $p) }}">
                      PDF
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
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th>Liquidación</th>
              <th>Pedido</th>
              <th>Fecha</th>
              <th class="text-right">Total</th>
              <th class="text-right">Pagado</th>
              <th class="text-right">Saldo</th>
              <th class="text-right" style="width: 180px;"></th>
            </tr>
          </thead>
          <tbody>
            @forelse($liquidaciones as $l)
              <tr>
                <td>#{{ $l->id }}</td>
                <td>
                  @can('pedidos.show')
                    <a href="{{ route('admin.pedidos.show', $l->pedido_id) }}">Pedido #{{ $l->pedido_id }}</a>
                  @else
                    Pedido #{{ $l->pedido_id }}
                  @endcan
                </td>
                <td>{{ optional($l->liquidado_at)->format('d/m/Y H:i') }}</td>
                <td class="text-right">{{ $fmt($l->total_gs) }}</td>
                <td class="text-right">{{ $fmt($l->aplicado_gs) }}</td>
                <td class="text-right {{ (int)$l->saldo_gs > 0 ? 'text-danger' : 'text-success' }}">
                  {{ $fmt($l->saldo_gs) }}
                </td>
                <td class="text-right">
                  @can('pedidos.liquidar')
                    <a class="btn btn-sm btn-outline-secondary"
                       href="{{ route('admin.pedidos.liquidar', $l->pedido_id) }}">
                      Ver liquidación
                    </a>
                  @endcan
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
    
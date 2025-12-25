@extends('layouts.admin')

@section('title', 'Pedidos')
@section('content_header', 'Pedidos')

@section('content')

@if (session('success'))
  <div class="alert alert-success alert-dismissible fade show">
    {{ session('success') }}
    <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
      <span aria-hidden="true">&times;</span>
    </button>
  </div>
@endif

<style>
  .card-soft{
    border: 1px solid rgba(148,163,184,.35);
    border-radius: .85rem;
    background: #fff;
    box-shadow: 0 10px 25px rgba(15,23,42,.06);
  }
  .badge-soft{
    border: 1px solid rgba(148,163,184,.35);
    font-weight: 700;
    padding: .35rem .55rem;
  }
  .money { font-variant-numeric: tabular-nums; }
  .table thead th{
    background: #f8fafc;
    border-bottom: 1px solid rgba(148,163,184,.35);
    font-size: .85rem;
  }
  .btn-xs{ padding: .2rem .35rem; font-size: .75rem; border-radius: .45rem; }
  .nowrap{ white-space: nowrap; }
</style>

<div class="card card-soft">
  <div class="card-header d-flex justify-content-between align-items-center">

    {{-- Filtros de búsqueda --}}
    <form method="GET" action="{{ route('admin.pedidos.index') }}" class="form-inline">
      <div class="input-group input-group-sm mr-2">
        <input type="text" name="search" class="form-control"
               placeholder="Buscar por código o paciente..."
               value="{{ $search }}">
      </div>

      <div class="input-group input-group-sm mr-2">
        <select name="estado" class="form-control">
          <option value="">-- Estado --</option>
          @foreach ([
            'pendiente'  => 'Pendiente',
            'en_proceso' => 'En proceso',
            'finalizado' => 'Finalizado',
            'cancelado'  => 'Cancelado',
          ] as $val => $label)
            <option value="{{ $val }}" @selected($estado === $val)>{{ $label }}</option>
          @endforeach
        </select>
      </div>

      <button type="submit" class="btn btn-primary btn-sm">
        <i class="fas fa-search"></i> Filtrar
      </button>

      @if ($search || $estado)
        <a href="{{ route('admin.pedidos.index') }}" class="btn btn-link btn-sm ml-1">
          Limpiar
        </a>
      @endif
    </form>

    {{-- Botón "Nuevo pedido" --}}
    @can('pedidos.create')
      <a href="{{ route('admin.pedidos.create') }}" class="btn btn-success btn-sm">
        <i class="fas fa-plus"></i> Nuevo pedido
      </a>
    @endcan
  </div>

  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover table-sm mb-0">
        <thead>
          <tr>
            <th style="width: 40px;">#</th>
            <th>Código</th>

            @if($isAdmin)
              <th>Clínica</th>
            @endif

            <th>Paciente</th>
            <th class="text-center">Prioridad</th>
            <th class="text-center">Estado</th>

            {{-- ✅ Pago --}}
            <th class="text-center">Pago</th>

            <th class="nowrap">Fecha solicitud</th>
            <th class="nowrap">Agendado para</th>
            <th class="text-center" style="width: 260px;">Acciones</th>
          </tr>
        </thead>

        <tbody>
          @forelse ($pedidos as $p)
            @php
              $liqId = $p->liq_id ?? null;

              // ✅ saldo real: preferimos el calculado del query (si existe),
              // si no existe, intentamos fallback por total - aplicado,
              // y si no es posible, queda NULL (NO lo convertimos a 0).
              $saldoCalc = null;

              if (!is_null($p->liq_saldo_calc_gs)) {
                  $saldoCalc = (int) $p->liq_saldo_calc_gs;
              } elseif (!is_null($p->liq_saldo_gs)) {
                  $saldoCalc = (int) $p->liq_saldo_gs;
              } elseif (!is_null($p->liq_total_gs)) {
                  $saldoCalc = max(0, (int)$p->liq_total_gs - (int)($p->liq_aplicado_gs ?? 0));
              }

              // ✅ Cancelado SOLO si hay liquidación y saldo es calculable y <= 0
              $pagoCancelado = (!is_null($liqId) && !is_null($saldoCalc) && $saldoCalc <= 0);

              $pagoLabel = $pagoCancelado ? 'Cancelado' : 'Pendiente';
              $pagoBadge = $pagoCancelado ? 'success' : 'warning';

              $verPagosUrl = $liqId
                ? route('admin.estado_cuenta.index', ['tab' => 'pagos', 'liquidacion_id' => $liqId])
                : route('admin.estado_cuenta.index', ['tab' => 'pagos', 'clinica_id' => $p->clinica_id]);
            @endphp

            <tr>
              <td>
                {{ ($pedidos->currentPage() - 1) * $pedidos->perPage() + $loop->iteration }}
              </td>

              <td class="nowrap">
                <div class="font-weight-bold">{{ $p->codigo }}</div>
                <div class="text-muted small">{{ $p->codigo_pedido ?? '' }}</div>
              </td>

              @if($isAdmin)
                <td>{{ $p->clinica->nombre ?? '-' }}</td>
              @endif

              <td>
                @if ($p->paciente)
                  <div class="font-weight-bold">{{ $p->paciente->apellido }} {{ $p->paciente->nombre }}</div>
                @else
                  -
                @endif
              </td>

              <td class="text-center">
                <span class="badge badge-{{ $p->prioridad === 'urgente' ? 'danger' : 'secondary' }} badge-soft">
                  {{ ucfirst($p->prioridad ?? 'normal') }}
                </span>
              </td>

              <td class="text-center">
                @php
                  $mapEstado = [
                    'pendiente'  => 'warning',
                    'en_proceso' => 'info',
                    'finalizado' => 'success',
                    'cancelado'  => 'secondary',
                  ];
                  $color = $mapEstado[$p->estado] ?? 'light';
                @endphp
                <span class="badge badge-{{ $color }} badge-soft">
                  {{ ucfirst($p->estado) }}
                </span>
              </td>

              {{-- ✅ Pago --}}
              <td class="text-center">
                <span class="badge badge-{{ $pagoBadge }} badge-soft">
                  {{ $pagoLabel }}
                </span>

                @if(!is_null($liqId))
                  <div class="small text-muted money">
                    Saldo: {{ number_format((int)($saldoCalc ?? 0), 0, ',', '.') }} Gs
                  </div>
                @endif
              </td>

              <td class="nowrap">
                @if ($p->fecha_solicitud)
                  {{ \Carbon\Carbon::parse($p->fecha_solicitud)->format('d/m/Y') }}
                @else
                  -
                @endif
              </td>

              <td class="nowrap">
                @if ($p->fecha_agendada)
                  {{ \Carbon\Carbon::parse($p->fecha_agendada)->format('d/m/Y') }}
                  @if ($p->hora_agendada)
                    {{ ' ' . \Carbon\Carbon::parse($p->hora_agendada)->format('H:i') }}
                  @endif
                @else
                  -
                @endif
              </td>

              <td class="text-center nowrap">
                @can('pedidos.view')
                  <a href="{{ route('admin.pedidos.show', $p) }}"
                     class="btn btn-xs btn-outline-secondary" title="Ver">
                    <i class="fas fa-eye"></i>
                  </a>
                @endcan

                @can('pedidos.update')
                  @if (auth()->user()->hasRole('admin') || $p->estado === 'pendiente')
                    <a href="{{ route('admin.pedidos.edit', $p) }}"
                       class="btn btn-xs btn-outline-primary" title="Editar">
                      <i class="fas fa-edit"></i>
                    </a>
                  @endif
                @endcan

                @can('pedidos.view')
                  <a href="{{ route('admin.pedidos.pdf', $p) }}"
                     class="btn btn-xs btn-outline-info" title="Descargar PDF" target="_blank">
                    <i class="fas fa-file-pdf"></i>
                  </a>
                @endcan

                @canany(['pagos.view','pagos.show','estado_cuenta.view'])
                  <a href="{{ $verPagosUrl }}"
                     class="btn btn-xs btn-outline-success" title="Ver pagos">
                    <i class="fas fa-receipt"></i>
                  </a>
                @endcanany

                @role('admin')
                  @can('pedidos.delete')
                    <form action="{{ route('admin.pedidos.destroy', $p) }}"
                          method="POST"
                          class="d-inline-block"
                          onsubmit="return confirm('¿Eliminar este pedido?');">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-xs btn-outline-danger" title="Eliminar">
                        <i class="fas fa-trash"></i>
                      </button>
                    </form>
                  @endcan
                @endrole
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="{{ $isAdmin ? 10 : 9 }}" class="text-center text-muted py-3">
                No hay pedidos registrados.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  @if ($pedidos->hasPages())
    <div class="card-footer">
      {{ $pedidos->links() }}
    </div>
  @endif
</div>
@endsection

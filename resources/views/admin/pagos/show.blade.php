@extends('layouts.admin')
@section('title','Detalle de Pago')
@section('content_header','Detalle de Pago')

@section('content')
@php
  $fmtGs = fn($n) => number_format((int)$n, 0, ',', '.');
  $aplicado = (int) ($pago->aplicaciones_sum_monto ?? $pago->aplicaciones->sum('monto_gs'));
  $saldoFavor = max(0, (int)$pago->monto_gs - $aplicado);
@endphp

<div class="card mb-3">
  <div class="card-header d-flex align-items-center justify-content-between">
    <div>
      <strong>Pago #{{ $pago->id }}</strong>
      <div class="small text-muted">Clínica: {{ $pago->clinica->nombre ?? '—' }}</div>
    </div>

    <div class="d-flex gap-2">
      @can('pagos.pdf')
        <a class="btn btn-sm btn-primary" target="_blank" href="{{ route('admin.pagos.pdf', $pago) }}">
          <i class="fas fa-file-pdf mr-1"></i> PDF recibo
        </a>
      @endcan
      <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.liquidaciones.pedidos_liquidados') }}">
        Volver
      </a>
      @can('pagos.delete')
  <form method="POST"
        action="{{ route('admin.pagos.destroy', $pago) }}"
        onsubmit="return confirm('¿Eliminar este pago? Se revertirán los montos aplicados.');"
        style="display:inline-block;">
    @csrf
    @method('DELETE')
    <button class="btn btn-sm btn-danger">
      <i class="fas fa-trash mr-1"></i> Eliminar
    </button>
  </form>
@endcan

    </div>
  </div>

  <div class="card-body">
    <div class="row">
      <div class="col-md-3">
        <div class="small text-muted">Fecha</div>
        <div class="fw-semibold">{{ $pago->fecha }}</div>
      </div>
      <div class="col-md-3">
        <div class="small text-muted">Método</div>
        <div class="fw-semibold">{{ ucfirst($pago->metodo) }}</div>
      </div>
      <div class="col-md-3">
        <div class="small text-muted">Monto</div>
        <div class="fw-semibold">Gs {{ $fmtGs($pago->monto_gs) }}</div>
      </div>
      <div class="col-md-3">
        <div class="small text-muted">Cajero</div>
        <div class="fw-semibold">{{ $pago->user->name ?? ('User #'.$pago->user_id) }}</div>
      </div>
    </div>

    @if($pago->referencia || $pago->observacion)
      <hr>
      <div class="row">
        <div class="col-md-6">
          <div class="small text-muted">Referencia</div>
          <div class="fw-semibold">{{ $pago->referencia ?: '—' }}</div>
        </div>
        <div class="col-md-6">
          <div class="small text-muted">Observación</div>
          <div class="fw-semibold">{{ $pago->observacion ?: '—' }}</div>
        </div>
      </div>
    @endif

    <hr>

    <div class="d-flex align-items-center justify-content-between">
      <div>
        <div class="small text-muted">Aplicado</div>
        <div class="h5 mb-0">Gs {{ $fmtGs($aplicado) }}</div>
      </div>
      <div class="text-right">
        <div class="small text-muted">Saldo a favor</div>
        @if($saldoFavor > 0)
          <div class="h5 mb-0 text-success">Gs {{ $fmtGs($saldoFavor) }}</div>
        @else
          <div class="h6 mb-0 text-muted">—</div>
        @endif
      </div>
    </div>

  </div>
</div>

<div class="card">
  <div class="card-header">
    <strong>Aplicaciones del pago</strong>
  </div>

  <div class="card-body p-0 table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="thead-light">
        <tr>
          <th>Pedido</th>
          <th class="text-end">Monto aplicado</th>
        </tr>
      </thead>
      <tbody>
        @foreach($pago->aplicaciones as $app)
          @php
            $liq = $app->liquidacion;
            $pedido = $liq?->pedido;
          @endphp
          <tr>
            <td class="fw-semibold">
              {{ $pedido->codigo_pedido ?? ('#'.$liq->pedido_id) ?? '—' }}
              <div class="small text-muted">Liq #{{ $liq->id ?? '—' }}</div>
            </td>
            <td class="text-end">Gs {{ $fmtGs($app->monto_gs) }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>

@endsection

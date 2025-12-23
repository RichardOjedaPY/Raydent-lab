@extends('layouts.admin')
@section('title','Detalle de Pago')
@section('content_header','Detalle de Pago')

@section('content')
@php
  $fmtGs = fn($n) => number_format((int)$n, 0, ',', '.');
@endphp

@if(session('ok'))
  <div class="alert alert-success">{{ session('ok') }}</div>
@endif

<div class="card">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
      <div>
        <div class="h5 mb-1">Recibo de Pago #{{ $pago->id }}</div>
        <div class="text-muted small">
          Clínica: <strong>{{ $pago->clinica->nombre ?? '—' }}</strong> |
          Fecha: <strong>{{ \Carbon\Carbon::parse($pago->fecha)->format('d/m/Y') }}</strong> |
          Método: <strong>{{ $pago->metodo }}</strong>
        </div>
        @if($pago->referencia)
          <div class="text-muted small">Referencia: {{ $pago->referencia }}</div>
        @endif
        @if($pago->observacion)
          <div class="text-muted small">Obs: {{ $pago->observacion }}</div>
        @endif
      </div>

      <div class="text-right">
        <div class="small text-muted">Total pagado</div>
        <div class="h4 mb-2">Gs {{ $fmtGs($pago->monto_gs) }}</div>

        <a class="btn btn-primary"
           href="{{ route('admin.pagos.pdf', $pago) }}">
          Descargar PDF
        </a>

        <a class="btn btn-outline-secondary"
           href="{{ route('admin.liquidaciones.pedidos_liquidados') }}">
          Volver
        </a>
      </div>
    </div>

    <hr>

    <div class="row">
      <div class="col-md-4">
        <div class="small text-muted">Aplicado a pedidos</div>
        <div class="h5">Gs {{ $fmtGs($aplicadoGs) }}</div>
      </div>
      <div class="col-md-4">
        <div class="small text-muted">Pago a cuenta</div>
        <div class="h5 {{ $aCuentaGs > 0 ? 'text-warning' : '' }}">Gs {{ $fmtGs($aCuentaGs) }}</div>
      </div>
      <div class="col-md-4">
        <div class="small text-muted">Registrado por</div>
        <div class="h6 mb-0">{{ $pago->user->name ?? '—' }}</div>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header"><strong>Pedidos incluidos en este pago</strong></div>
  <div class="card-body p-0 table-responsive">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th>Pedido</th>
          <th>Paciente</th>
          <th class="text-end">Monto aplicado</th>
        </tr>
      </thead>
      <tbody>
        @forelse($pago->aplicaciones as $ap)
          @php
            $pedido = $ap->liquidacion?->pedido;
          @endphp
          <tr>
            <td>{{ $pedido->codigo_pedido ?? ('#'.$pedido->id ?? '—') }}</td>
            <td>{{ $pedido->paciente->nombre ?? '—' }}</td>
            <td class="text-end">Gs {{ $fmtGs($ap->monto_gs) }}</td>
          </tr>
        @empty
          <tr><td colspan="3" class="text-center text-muted py-3">Este pago no tiene aplicaciones (queda como pago a cuenta).</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection

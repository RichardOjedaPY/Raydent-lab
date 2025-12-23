<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Recibo Pago #{{ $pago->id }}</title>
  <style>
    @page { margin: 10mm; }
    * { box-sizing: border-box; font-family: DejaVu Sans, Arial, sans-serif; }
    body { font-size: 12px; color: #111; }
    .row { display: flex; justify-content: space-between; gap: 10px; }
    .box { border: 1px solid #cbd5e1; padding: 8px; border-radius: 6px; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { border-bottom: 1px solid #e5e7eb; padding: 6px; }
    th { text-align: left; background: #f8fafc; }
    .right { text-align: right; }
    .muted { color: #475569; }
    .h { font-size: 16px; font-weight: 700; }
  </style>
</head>
<body>
@php
  $fmtGs = fn($n) => number_format((int)$n, 0, ',', '.');
@endphp

<div class="row">
  <div class="box" style="flex:1;">
    <div class="h">Recibo de Pago</div>
    <div class="muted">N°: <strong>#{{ $pago->id }}</strong></div>
    <div class="muted">Fecha: <strong>{{ \Carbon\Carbon::parse($pago->fecha)->format('d/m/Y') }}</strong></div>
    <div class="muted">Clínica: <strong>{{ $pago->clinica->nombre ?? '—' }}</strong></div>
    <div class="muted">Método: <strong>{{ $pago->metodo }}</strong></div>
    @if($pago->referencia)<div class="muted">Ref: {{ $pago->referencia }}</div>@endif
  </div>

  <div class="box" style="width: 240px;">
    <div class="muted">Total pagado</div>
    <div class="h">Gs {{ $fmtGs($pago->monto_gs) }}</div>
    <div class="muted">Aplicado: Gs {{ $fmtGs($aplicadoGs) }}</div>
    <div class="muted">A cuenta: Gs {{ $fmtGs($aCuentaGs) }}</div>
  </div>
</div>

@if($pago->observacion)
  <div class="box" style="margin-top:10px;">
    <div class="muted"><strong>Observación:</strong> {{ $pago->observacion }}</div>
  </div>
@endif

<table>
  <thead>
    <tr>
      <th>Pedido</th>
      <th>Paciente</th>
      <th class="right">Monto aplicado</th>
    </tr>
  </thead>
  <tbody>
    @forelse($pago->aplicaciones as $ap)
      @php $pedido = $ap->liquidacion?->pedido; @endphp
      <tr>
        <td>{{ $pedido->codigo_pedido ?? ('#'.$pedido->id ?? '—') }}</td>
        <td>{{ $pedido->paciente->nombre ?? '—' }}</td>
        <td class="right">Gs {{ $fmtGs($ap->monto_gs) }}</td>
      </tr>
    @empty
      <tr>
        <td colspan="3" class="muted">Este pago no tiene aplicaciones (queda como pago a cuenta).</td>
      </tr>
    @endforelse
  </tbody>
</table>

<div class="muted" style="margin-top:10px;">
  Emitido por: {{ $pago->user->name ?? '—' }}
</div>

</body>
</html>

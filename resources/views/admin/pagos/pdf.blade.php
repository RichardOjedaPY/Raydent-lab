<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Recibo de Pago #{{ $pago->id }}</title>
  <style>
    @page { margin: 10mm; }
    * { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; }
    .row { display:flex; justify-content:space-between; }
    .muted { color:#6b7280; }
    .h1 { font-size: 16px; font-weight: 700; margin:0; }
    .box { border:1px solid #e5e7eb; padding:10px; border-radius:6px; }
    table { width:100%; border-collapse:collapse; }
    th,td { border-bottom:1px solid #e5e7eb; padding:8px 6px; }
    th { text-align:left; background:#f3f4f6; }
    .right { text-align:right; }
  </style>
</head>
<body>
@php
  $fmtGs = fn($n) => number_format((int)$n, 0, ',', '.');
  $aplicado = (int) ($pago->aplicaciones->sum('monto_gs'));
  $saldoFavor = max(0, (int)$pago->monto_gs - $aplicado);
@endphp

<div class="row">
  <div>
    <p class="h1">Recibo de dinero</p>
    <div class="muted">Pago #{{ $pago->id }}</div>
  </div>
  <div class="right">
    <div><strong>Fecha:</strong> {{ $pago->fecha }}</div>
    <div><strong>Método:</strong> {{ ucfirst($pago->metodo) }}</div>
  </div>
</div>

<br>

<div class="box">
  <div><strong>Clínica:</strong> {{ $pago->clinica->nombre ?? '—' }}</div>
  <div class="muted"><strong>Cajero:</strong> {{ $pago->user->name ?? ('User #'.$pago->user_id) }}</div>
  @if($pago->referencia)
    <div class="muted"><strong>Referencia:</strong> {{ $pago->referencia }}</div>
  @endif
  @if($pago->observacion)
    <div class="muted"><strong>Obs.:</strong> {{ $pago->observacion }}</div>
  @endif
</div>

<br>

<table>
  <tr>
    <th>Pedido / Liquidación</th>
    <th class="right">Monto aplicado</th>
  </tr>
  @foreach($pago->aplicaciones as $app)
    @php
      $liq = $app->liquidacion;
      $pedido = $liq?->pedido;
      $pedidoCodigo = $pedido->codigo_pedido ?? ('#'.$liq->pedido_id) ?? '—';
    @endphp
    <tr>
      <td>
        <strong>{{ $pedidoCodigo }}</strong>
        <div class="muted">Liq #{{ $liq->id ?? '—' }}</div>
      </td>
      <td class="right">Gs {{ $fmtGs($app->monto_gs) }}</td>
    </tr>
  @endforeach
</table>

<br>

<div class="box">
  <div class="row">
    <div><strong>Total pagado:</strong></div>
    <div class="right"><strong>Gs {{ $fmtGs($pago->monto_gs) }}</strong></div>
  </div>
  <div class="row">
    <div><strong>Total aplicado:</strong></div>
    <div class="right">Gs {{ $fmtGs($aplicado) }}</div>
  </div>
  <div class="row">
    <div><strong>Saldo a favor:</strong></div>
    <div class="right">Gs {{ $fmtGs($saldoFavor) }}</div>
  </div>
</div>

<br><br>
<div class="muted">
  Firma: _______________________________
</div>

</body>
</html>

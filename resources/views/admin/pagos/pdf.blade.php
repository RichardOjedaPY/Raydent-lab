<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Recibo de Pago #{{ $pago->id }}</title>

  <style>
    /* Reservar espacio para footer fijo */
    @page { margin: 10mm 10mm 18mm 10mm; }

    * { box-sizing: border-box; font-family: DejaVu Sans, Arial, sans-serif; }
    body { margin: 0; font-size: 11px; color: #111827; line-height: 1.25; }

    /* Paleta Raydent */
    .brand { color: #005596; }
    .bg-brand { background: #005596; color: #fff; }

    /* Utilidades */
    .w-100 { width: 100%; }
    .right { text-align: right; }
    .muted { color: #6b7280; }
    .bold { font-weight: 700; }
    .h1 { font-size: 16px; font-weight: 800; margin: 0; }
    .small { font-size: 9px; }
    .uppercase { text-transform: uppercase; }
    .divider { height: 1px; background: rgba(148,163,184,.45); margin: 8px 0; }

    /* Cards */
    .box {
      border: 1px solid rgba(148,163,184,.45);
      padding: 10px;
      border-radius: 8px;
      background: #fff;
    }
    .soft {
      background: #f8fafc;
      border: 1px solid rgba(148,163,184,.35);
    }

    /* Header */
    .header-wrap { border-bottom: 2px solid #005596; padding-bottom: 8px; margin-bottom: 10px; }
    .logo-title { font-size: 26px; font-weight: 900; margin: 0; color: #005596; line-height: 1; }
    .logo-sub { font-size: 9px; color: #64748b; margin-top: 2px; }

    /* Tabla */
    table { width: 100%; border-collapse: collapse; }
    .tbl thead th {
      text-align: left;
      background: #f1f5f9;
      border-bottom: 1px solid rgba(148,163,184,.55);
      padding: 7px 6px;
      font-size: 10px;
      letter-spacing: .3px;
      text-transform: uppercase;
      color: #0f172a;
    }
    .tbl tbody td {
      border-bottom: 1px solid rgba(148,163,184,.30);
      padding: 8px 6px;
      vertical-align: top;
    }
    .nowrap { white-space: nowrap; }

    /* Footer fijo */
    .footer-legal {
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      background: #005596;
      color: white;
      text-align: center;
      font-size: 8px;
      padding: 6px 8px;
      border-radius: 6px 6px 0 0;
    }
  </style>
</head>

<body>
@php
  use Carbon\Carbon;

  $fmtGs = fn($n) => number_format((int)($n ?? 0), 0, ',', '.');
  $aplicado = (int) ($pago->aplicaciones->sum('monto_gs'));
  $saldoFavor = max(0, (int)$pago->monto_gs - $aplicado);

  $fecha = $pago->fecha ? Carbon::parse($pago->fecha)->format('d/m/Y') : (string)$pago->fecha;
  $metodo = ucfirst((string)($pago->metodo ?? '—'));
@endphp

{{-- =================== CABECERA =================== --}}
<div class="header-wrap">
  <table class="w-100">
    <tr>
      <td style="width: 60%;">
        <div class="logo-title">Raydent</div>
        <div class="logo-sub">Radiología Odontológica Digital</div>
      </td>
      <td style="width: 40%;" class="right">
        <div class="bold brand" style="font-size: 12px;">Recibo de dinero</div>
        <div class="muted">Pago <span class="bold">#{{ $pago->id }}</span></div>
        <div class="muted">Fecha: <span class="bold">{{ $fecha }}</span></div>
        <div class="muted">Método: <span class="bold">{{ $metodo }}</span></div>
      </td>
    </tr>
  </table>
</div>

{{-- =================== DATOS =================== --}}
<div class="box">
  <table class="w-100" style="table-layout: fixed;">
    <tr>
      <td style="width: 60%; padding-right: 8px;">
        <div class="h1">Recibo de Pago</div>
        <div class="muted" style="margin-top:2px;">Constancia de recepción de dinero.</div>

        <div class="divider"></div>

        <div><span class="bold">Clínica:</span> {{ $pago->clinica->nombre ?? '—' }}</div>
        <div class="muted"><span class="bold">Cajero:</span> {{ $pago->user->name ?? ('User #'.$pago->user_id) }}</div>

        @if($pago->referencia)
          <div class="muted"><span class="bold">Referencia:</span> {{ $pago->referencia }}</div>
        @endif

        @if($pago->observacion)
          <div class="divider"></div>
          <div class="soft" style="padding: 8px; border-radius: 8px;">
            <div class="muted bold" style="margin-bottom: 2px;">Observación</div>
            <div>{{ $pago->observacion }}</div>
          </div>
        @endif
      </td>

      <td style="width: 40%; padding-left: 8px;">
        <div class="box soft">
          <div class="muted uppercase small" style="letter-spacing:.3px;">Resumen</div>
          <div class="divider"></div>

          <table class="w-100">
            <tr>
              <td class="bold">Total pagado:</td>
              <td class="right bold nowrap">Gs {{ $fmtGs($pago->monto_gs) }}</td>
            </tr>
            <tr>
              <td class="bold">Total aplicado:</td>
              <td class="right nowrap">Gs {{ $fmtGs($aplicado) }}</td>
            </tr>
            <tr>
              <td class="bold">Saldo a favor:</td>
              <td class="right nowrap">Gs {{ $fmtGs($saldoFavor) }}</td>
            </tr>
          </table>

          <div class="muted small" style="margin-top: 8px;">
            Generado el {{ now()->format('d/m/Y H:i') }}.
          </div>
        </div>
      </td>
    </tr>
  </table>
</div>

{{-- =================== DETALLE =================== --}}
<div style="margin-top: 10px;">
  <div class="bg-brand" style="padding: 6px 10px; border-radius: 8px;">
    <span class="bold uppercase" style="font-size: 10px; letter-spacing: .4px;">Detalle de aplicación</span>
  </div>

  <div class="box" style="border-top-left-radius: 0; border-top-right-radius: 0;">
    <table class="tbl">
      <thead>
        <tr>
          <th>Pedido / Liquidación</th>
          <th class="right">Monto aplicado</th>
        </tr>
      </thead>
      <tbody>
        @forelse($pago->aplicaciones as $app)
          @php
            $liq = $app->liquidacion;
            $pedido = $liq?->pedido;
            $pedidoCodigo = $pedido->codigo_pedido ?? (isset($liq->pedido_id) ? ('#'.$liq->pedido_id) : '—');
          @endphp
          <tr>
            <td>
              <div class="bold">{{ $pedidoCodigo }}</div>
              <div class="muted small">Liq #{{ $liq->id ?? '—' }}</div>
            </td>
            <td class="right bold nowrap">Gs {{ $fmtGs($app->monto_gs) }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="2" class="muted">
              Este pago no tiene aplicaciones (queda como pago a cuenta).
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

{{-- =================== FIRMA =================== --}}
<div style="margin-top: 14px;">
  <table class="w-100">
    <tr>
      <td style="width: 55%;"></td>
      <td style="width: 45%;" class="right">
        <div class="muted small">Firma</div>
        <div style="border-bottom: 1px solid rgba(15,23,42,.65); height: 22px;"></div>
      </td>
    </tr>
  </table>
</div>

{{-- =================== PIE FIJO =================== --}}
<div class="footer-legal">
  Av. Cesar Gionotti c/ Calle Cnel Bogado - Hernandarias · Edificio Dinámica al costado de IPS<br>
  Cel. (0973) 665 779 · www.raydentradiologia.com.py · raydentradiologia511@gmail.com
</div>

</body>
</html>

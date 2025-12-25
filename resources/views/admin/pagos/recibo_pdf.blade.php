<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Recibo Pago #{{ $pago->id }}</title>

  <style>
    /* Reservamos espacio inferior para footer fijo */
    @page { margin: 10mm 10mm 18mm 10mm; }

    * { box-sizing: border-box; font-family: DejaVu Sans, Arial, sans-serif; }
    body { margin: 0; font-size: 11px; color: #111827; line-height: 1.25; }

    /* Paleta Raydent */
    .brand { color: #005596; }
    .bg-brand { background: #005596; color: #fff; }

    /* Helpers */
    .w-100 { width: 100%; }
    .right { text-align: right; }
    .center { text-align: center; }
    .muted { color: #475569; }
    .bold { font-weight: 700; }
    .uppercase { text-transform: uppercase; }
    .small { font-size: 9px; }
    .h1 { font-size: 18px; font-weight: 800; letter-spacing: .2px; margin: 0; }

    /* Tarjetas / cajas */
    .card {
      border: 1px solid rgba(148,163,184,.45);
      border-radius: 8px;
      padding: 10px;
      background: #fff;
    }
    .soft {
      background: #f8fafc;
      border: 1px solid rgba(148,163,184,.35);
    }

    /* Separadores */
    .divider { height: 1px; background: rgba(148,163,184,.45); margin: 8px 0; }

    /* Tabla detalle */
    table { border-collapse: collapse; width: 100%; }
    .tbl th, .tbl td { padding: 7px 6px; vertical-align: top; }
    .tbl thead th {
      background: #f1f5f9;
      border-bottom: 1px solid rgba(148,163,184,.55);
      font-size: 10px;
      letter-spacing: .3px;
      text-transform: uppercase;
      color: #0f172a;
    }
    .tbl tbody td { border-bottom: 1px solid rgba(148,163,184,.30); }
    .tbl .right { white-space: nowrap; }

    /* Badge */
    .badge {
      display: inline-block;
      padding: 2px 8px;
      border-radius: 999px;
      border: 1px solid rgba(148,163,184,.55);
      background: #fff;
      font-size: 9px;
      font-weight: 700;
      color: #0f172a;
    }

    /* Footer fijo */
    .footer-legal {
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      background: #005596;
      color: #fff;
      text-align: center;
      font-size: 8px;
      padding: 6px 8px;
      border-radius: 6px 6px 0 0;
    }

    /* Header */
    .header-wrap { border-bottom: 2px solid #005596; padding-bottom: 8px; margin-bottom: 10px; }
    .logo-title { font-size: 26px; font-weight: 900; margin: 0; color: #005596; line-height: 1; }
    .logo-sub { font-size: 9px; color: #64748b; margin-top: 2px; }

    /* Totales */
    .total-label { font-size: 10px; color: #64748b; text-transform: uppercase; letter-spacing: .3px; }
    .total-amount { font-size: 18px; font-weight: 900; color: #0f172a; margin: 2px 0 0; }
    .kv td { padding: 2px 0; }
  </style>
</head>

<body>
@php
  use Carbon\Carbon;

  $fmtGs = fn($n) => number_format((int)($n ?? 0), 0, ',', '.');

  $fecha = $pago->fecha ? Carbon::parse($pago->fecha)->format('d/m/Y') : '—';

  // Normalización de textos
  $metodo = strtoupper((string)($pago->metodo ?? '—'));
  $referencia = $pago->referencia ?: null;

  // Si querés, podés mapear método a un “badge” más lindo
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
        <div class="bold brand" style="font-size: 12px;">Recibo de Pago</div>
        <div class="muted">N°: <span class="bold">#{{ $pago->id }}</span></div>
        <div class="muted">Fecha: <span class="bold">{{ $fecha }}</span></div>
      </td>
    </tr>
  </table>
</div>

{{-- =================== BLOQUE PRINCIPAL =================== --}}
<table class="w-100" style="table-layout: fixed;">
  <tr>
    {{-- Columna izquierda --}}
    <td style="width: 65%; padding-right: 8px;">
      <div class="card">
        <div class="h1">Recibo de Pago</div>
        <div class="muted" style="margin-top: 2px;">
          Comprobante de recepción de dinero y aplicación a pedidos, según detalle.
        </div>

        <div class="divider"></div>

        <table class="w-100 kv">
          <tr>
            <td style="width: 22%;" class="muted bold">Clínica:</td>
            <td style="width: 78%;">{{ $pago->clinica->nombre ?? '—' }}</td>
          </tr>
          <tr>
            <td class="muted bold">Método:</td>
            <td>
              <span class="badge">{{ $metodo }}</span>
              @if($referencia)
                <span class="muted" style="margin-left: 6px;">Ref: {{ $referencia }}</span>
              @endif
            </td>
          </tr>
          <tr>
            <td class="muted bold">Emitido por:</td>
            <td>{{ $pago->user->name ?? '—' }}</td>
          </tr>
        </table>

        @if($pago->observacion)
          <div class="divider"></div>
          <div class="soft" style="padding: 8px; border-radius: 8px;">
            <div class="muted bold" style="margin-bottom: 2px;">Observación</div>
            <div>{{ $pago->observacion }}</div>
          </div>
        @endif
      </div>
    </td>

    {{-- Columna derecha (totales) --}}
    <td style="width: 35%; padding-left: 8px;">
      <div class="card soft">
        <div class="total-label">Total pagado</div>
        <div class="total-amount">Gs {{ $fmtGs($pago->monto_gs) }}</div>

        <div class="divider"></div>

        <table class="w-100 kv">
          <tr>
            <td class="muted">Aplicado:</td>
            <td class="right bold">Gs {{ $fmtGs($aplicadoGs) }}</td>
          </tr>
          <tr>
            <td class="muted">A cuenta:</td>
            <td class="right bold">Gs {{ $fmtGs($aCuentaGs) }}</td>
          </tr>
        </table>

        <div style="margin-top: 10px; font-size: 9px;" class="muted">
          Este recibo es válido como constancia interna del sistema.
        </div>
      </div>
    </td>
  </tr>
</table>

{{-- =================== DETALLE =================== --}}
<div style="margin-top: 10px;">
  <div class="bg-brand" style="padding: 6px 10px; border-radius: 8px;">
    <span class="bold uppercase" style="font-size: 10px; letter-spacing: .4px;">Detalle de aplicación</span>
  </div>

  <div class="card" style="border-top-left-radius: 0; border-top-right-radius: 0;">
    <table class="tbl">
      <thead>
        <tr>
          <th style="width: 18%;">Pedido</th>
          <th style="width: 52%;">Paciente</th>
          <th style="width: 30%;" class="right">Monto aplicado</th>
        </tr>
      </thead>
      <tbody>
        @forelse($pago->aplicaciones as $ap)
          @php $pedido = $ap->liquidacion?->pedido; @endphp
          <tr>
            <td>
              {{ $pedido->codigo_pedido ?? (isset($pedido->id) ? ('#'.$pedido->id) : '—') }}
            </td>
            <td>
              {{ $pedido->paciente->apellido ?? '' }}
              {{ $pedido->paciente->nombre ?? '—' }}
            </td>
            <td class="right bold">Gs {{ $fmtGs($ap->monto_gs) }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="3" class="muted">
              Este pago no tiene aplicaciones (queda como pago a cuenta).
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>

    <div style="margin-top: 8px;" class="muted small">
      Generado el {{ now()->format('d/m/Y H:i') }}.
    </div>
  </div>
</div>

{{-- =================== PIE FIJO =================== --}}
<div class="footer-legal">
  Av. Cesar Gionotti c/ Calle Cnel Bogado - Hernandarias · Edificio Dinámica al costado de IPS<br>
  Cel. (0973) 665 779 · www.raydentradiologia.com.py · raydentradiologia511@gmail.com
</div>

</body>
</html>

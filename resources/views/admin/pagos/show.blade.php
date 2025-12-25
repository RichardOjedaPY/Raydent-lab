@extends('layouts.admin')
@section('title', 'Detalle de Pago')
@section('content_header', 'Detalle de Pago')

@section('content')
@php
    use Carbon\Carbon;

    $fmtGs = fn($n) => number_format((int) $n, 0, ',', '.');

    // Historial: si el controlador no mandó, caemos al pago actual
    $historial = collect($pagosRelacionados ?? [$pago]);

    // Permisos (para toggles en JS)
    $canPdf    = auth()->user()?->can('pagos.pdf') ?? false;
    $canDelete = auth()->user()?->can('pagos.delete') ?? false;

    // Payload JSON para cambiar detalles sin recargar
    $payload = $historial->map(function ($p) use ($fmtGs) {
        $aplicado = (int) ($p->aplicado_gs_sum ?? $p->aplicaciones?->sum('monto_gs') ?? 0);
        $saldoFavor = max(0, (int)$p->monto_gs - $aplicado);

        $fechaFmt = $p->fecha ? Carbon::parse($p->fecha)->format('d/m/Y') : '—';
        $horaFmt  = optional($p->created_at)->format('H:i'); // hora real del registro

        return [
            'id'          => (int) $p->id,

            // ✅ ya formateados para mostrar
            'fecha_fmt'   => $fechaFmt,
            'hora_fmt'    => $horaFmt,

            'metodo'      => (string) ($p->metodo ?? ''),
            'monto_gs'    => (int) ($p->monto_gs ?? 0),
            'aplicado_gs' => $aplicado,
            'saldo_favor' => $saldoFavor,
            'clinica'     => (string) ($p->clinica?->nombre ?? '—'),
            'cajero'      => (string) ($p->user?->name ?? ('User #' . ($p->user_id ?? ''))),
            'referencia'  => (string) ($p->referencia ?? ''),
            'observacion' => (string) ($p->observacion ?? ''),

            'pdf_url'     => route('admin.pagos.pdf', $p),
            'destroy_url' => route('admin.pagos.destroy', $p),
            'show_url'    => route('admin.pagos.show', $p),

            // Aplicaciones (de este pago)
            'apps' => collect($p->aplicaciones ?? [])->map(function ($app) {
                $liq = $app->liquidacion;
                $ped = $liq?->pedido;
                return [
                    'pedido_codigo' => (string) ($ped?->codigo_pedido ?? ($ped?->codigo ?? ('#' . ($liq?->pedido_id ?? '')))),
                    'liq_id'        => (int) ($liq?->id ?? 0),
                    'monto_gs'      => (int) ($app->monto_gs ?? 0),
                ];
            })->values()->all(),
        ];
    })->values()->all();

    // Seleccionado inicial = el pago actual
    $selectedId = (int) $pago->id;

    // Inicial (panel derecho)
    $aplicadoInicial   = (int) ($pago->aplicaciones_sum_monto ?? $pago->aplicaciones?->sum('monto_gs') ?? 0);
    $saldoFavorInicial = max(0, (int)$pago->monto_gs - $aplicadoInicial);

    $fechaInicial = $pago->fecha ? Carbon::parse($pago->fecha)->format('d/m/Y') : '—';
    $horaInicial  = optional($pago->created_at)->format('H:i');

    // Contexto opcional (pedido/liquidación)
    $pedidoCodigo = $pedido?->codigo_pedido ?? $pedido?->codigo ?? null;
    $liqId = $liq?->id ?? null;
@endphp

<style>
    .pay-list .list-group-item { cursor: pointer; }
    .pay-list .list-group-item.active {
        border-color: rgba(13,110,253,.35);
        background: rgba(13,110,253,.08);
        color: inherit;
    }
    .kpi {
        border: 1px solid rgba(148,163,184,.35);
        border-radius: .75rem;
        padding: .85rem .95rem;
        background: #fff;
    }
    .kpi .label { font-size: .78rem; color: #6b7280; }
    .kpi .value { font-size: 1.05rem; font-weight: 700; margin-top: .15rem; }
    .muted { color: #6b7280; }
</style>

<div class="row">
    {{-- LEFT: Historial --}}
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div>
                    <strong>Historial de pagos</strong>
                    @if($pedidoCodigo || $liqId)
                        <div class="small text-muted">
                            @if($pedidoCodigo) Pedido: <strong>{{ $pedidoCodigo }}</strong>@endif
                            @if($liqId) <span class="mx-1">•</span> Liq #{{ $liqId }} @endif
                        </div>
                    @endif
                </div>
                <span class="badge badge-light">{{ $historial->count() }}</span>
            </div>

            <div class="card-body p-0">
                <div class="list-group list-group-flush pay-list" id="payList">
                    @forelse($historial as $p)
                        @php
                            $ap = (int) ($p->aplicado_gs_sum ?? $p->aplicaciones?->sum('monto_gs') ?? 0);
                            $fechaPago = $p->fecha ? \Carbon\Carbon::parse($p->fecha)->format('d/m/Y') : '—';
                            $horaPago  = optional($p->created_at)->format('H:i');
                            $isActive  = ((int)$p->id === (int)$selectedId);
                        @endphp

                        <div class="list-group-item d-flex justify-content-between align-items-center {{ $isActive ? 'active' : '' }}"
                             data-pay-id="{{ (int)$p->id }}">
                            <div>
                                <div class="fw-semibold">Pago #{{ $p->id }}</div>

                                <div class="small text-muted">
                                    {{ $fechaPago }} — {{ $horaPago }} • {{ ucfirst($p->metodo) }}
                                </div>

                                <div class="small">
                                    <span class="text-success">Gs {{ $fmtGs($p->monto_gs) }}</span>
                                    <span class="mx-1 text-muted">•</span>
                                    <span class="text-muted">Aplicado: {{ $fmtGs($ap) }}</span>
                                </div>
                            </div>

                            <div class="text-right">
                                <div class="small text-muted">Cajero</div>
                                <div class="small">{{ $p->user->name ?? '—' }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="p-4 text-center text-muted">Sin pagos registrados.</div>
                    @endforelse
                </div>
            </div>

            <div class="card-footer d-flex justify-content-between">
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.liquidaciones.pedidos_liquidados') }}">
                    Volver
                </a>
                <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.liquidaciones.pedidos_liquidados') }}">
                    Ir a pedidos liquidados
                </a>
            </div>
        </div>
    </div>

    {{-- RIGHT: Detalle (cambia al seleccionar) --}}
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div>
                    <strong id="detTitle">Pago #{{ $pago->id }}</strong>
                    <div class="small text-muted">
                        Clínica: <span id="detClinica">{{ $pago->clinica->nombre ?? '—' }}</span>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    @can('pagos.pdf')
                        <a class="btn btn-sm btn-primary" id="btnPdf" target="_blank"
                           href="{{ route('admin.pagos.pdf', $pago) }}">
                            <i class="fas fa-file-pdf mr-1"></i> PDF recibo
                        </a>
                    @endcan

                    @can('pagos.delete')
                        <form method="POST" id="frmDelete" action="{{ route('admin.pagos.destroy', $pago) }}"
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
                <div class="row g-2">
                    <div class="col-md-3">
                        <div class="kpi">
                            <div class="label">Fecha</div>
                            {{-- ✅ este ID es clave para que el JS actualice --}}
                            <div class="value" id="detFechaHora">
                                {{ $fechaInicial }} — {{ $horaInicial }}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="kpi">
                            <div class="label">Método</div>
                            <div class="value" id="detMetodo">{{ ucfirst((string)$pago->metodo) }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="kpi">
                            <div class="label">Monto</div>
                            <div class="value" id="detMonto">Gs {{ $fmtGs($pago->monto_gs) }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="kpi">
                            <div class="label">Cajero</div>
                            <div class="value" id="detCajero">{{ $pago->user->name ?? '—' }}</div>
                        </div>
                    </div>
                </div>

                <div class="row mt-3 g-2" id="refObsWrap" style="{{ ($pago->referencia || $pago->observacion) ? '' : 'display:none;' }}">
                    <div class="col-md-6">
                        <div class="kpi">
                            <div class="label">Referencia</div>
                            <div class="value" style="font-size: .95rem;" id="detReferencia">{{ $pago->referencia ?: '—' }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="kpi">
                            <div class="label">Observación</div>
                            <div class="value" style="font-size: .95rem;" id="detObservacion">{{ $pago->observacion ?: '—' }}</div>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="row g-2">
                    <div class="col-md-6">
                        <div class="kpi">
                            <div class="label">Aplicado</div>
                            <div class="value" id="detAplicado">Gs {{ $fmtGs($aplicadoInicial) }}</div>
                        </div>
                    </div>
                    <div class="col-md-6 text-right">
                        <div class="kpi">
                            <div class="label">Saldo a favor</div>
                            <div class="value" id="detSaldoFavor">
                                @if($saldoFavorInicial > 0)
                                    <span class="text-success">Gs {{ $fmtGs($saldoFavorInicial) }}</span>
                                @else
                                    <span class="muted">—</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- Aplicaciones --}}
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <strong>Aplicaciones del pago</strong>
                <span class="small text-muted" id="appsCount"></span>
            </div>

            <div class="card-body p-0 table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Pedido</th>
                            <th class="text-end">Monto aplicado</th>
                        </tr>
                    </thead>
                    <tbody id="appsBody">
                        @forelse($pago->aplicaciones as $app)
                            @php
                                $liqRow = $app->liquidacion;
                                $pedRow = $liqRow?->pedido;
                            @endphp
                            <tr>
                                <td class="fw-semibold">
                                    {{ $pedRow->codigo_pedido ?? ($pedRow->codigo ?? ('#' . ($liqRow->pedido_id ?? '—'))) }}
                                    <div class="small text-muted">Liq #{{ $liqRow->id ?? '—' }}</div>
                                </td>
                                <td class="text-end">Gs {{ $fmtGs($app->monto_gs) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="text-center text-muted py-4">Sin aplicaciones.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<script>
(function () {
    function fmtGs(n) {
        n = parseInt(n || 0, 10);
        return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }

    const payload   = @json($payload);
    const selectedId = @json($selectedId);

    const canPdf    = @json($canPdf);
    const canDelete = @json($canDelete);

    const payList = document.getElementById('payList');

    function $(id){ return document.getElementById(id); }
    function setText(id, value){
        const el = $(id);
        if (!el) return;
        el.textContent = value ?? '';
    }
    function setHTML(id, html){
        const el = $(id);
        if (!el) return;
        el.innerHTML = html ?? '';
    }

    function findPay(id) {
        return payload.find(p => parseInt(p.id, 10) === parseInt(id, 10));
    }

    function setActiveRow(id) {
        if (!payList) return;
        payList.querySelectorAll('.list-group-item').forEach(function (it) {
            const pid = it.getAttribute('data-pay-id');
            it.classList.toggle('active', parseInt(pid,10) === parseInt(id,10));
        });
    }

    function renderApps(apps) {
        const body = $('appsBody');
        const count = $('appsCount');
        if (!body) return;

        apps = Array.isArray(apps) ? apps : [];

        if (count) count.textContent = apps.length ? (apps.length + ' item(s)') : '';

        if (!apps.length) {
            body.innerHTML = `<tr><td colspan="2" class="text-center text-muted py-4">Sin aplicaciones.</td></tr>`;
            return;
        }

        body.innerHTML = apps.map(function (a) {
            const ped = a.pedido_codigo || '—';
            const liq = a.liq_id ? ('Liq #' + a.liq_id) : '—';
            return `
                <tr>
                    <td class="fw-semibold">
                        ${ped}
                        <div class="small text-muted">${liq}</div>
                    </td>
                    <td class="text-end">Gs ${fmtGs(a.monto_gs)}</td>
                </tr>
            `;
        }).join('');
    }

    function capFirst(s){
        s = (s || '').toString();
        return s ? s.charAt(0).toUpperCase() + s.slice(1) : '—';
    }

    function renderDetail(p) {
        if (!p) return;

        setText('detTitle', 'Pago #' + p.id);
        setText('detClinica', p.clinica || '—');

        // ✅ Fecha + hora (sin 00:00:00)
        setText('detFechaHora', (p.fecha_fmt || '—') + ' — ' + (p.hora_fmt || '—'));

        setText('detMetodo', capFirst(p.metodo));
        setText('detMonto', 'Gs ' + fmtGs(p.monto_gs));
        setText('detCajero', p.cajero || '—');

        const refObsWrap = $('refObsWrap');
        const hasRefObs = (p.referencia && p.referencia.trim() !== '') || (p.observacion && p.observacion.trim() !== '');
        if (refObsWrap) refObsWrap.style.display = hasRefObs ? '' : 'none';

        setText('detReferencia', (p.referencia && p.referencia.trim() !== '') ? p.referencia : '—');
        setText('detObservacion', (p.observacion && p.observacion.trim() !== '') ? p.observacion : '—');

        setText('detAplicado', 'Gs ' + fmtGs(p.aplicado_gs));

        if (parseInt(p.saldo_favor,10) > 0) {
            setHTML('detSaldoFavor', `<span class="text-success">Gs ${fmtGs(p.saldo_favor)}</span>`);
        } else {
            setHTML('detSaldoFavor', `<span class="muted">—</span>`);
        }

        // Botones (PDF / Delete) con permisos
        if (canPdf) {
            const btnPdf = $('btnPdf');
            if (btnPdf && p.pdf_url) btnPdf.setAttribute('href', p.pdf_url);
        }
        if (canDelete) {
            const frm = $('frmDelete');
            if (frm && p.destroy_url) frm.setAttribute('action', p.destroy_url);
        }

        renderApps(p.apps || []);
        setActiveRow(p.id);

        // Opcional: actualizar URL sin recargar
        try {
            if (p.show_url) history.replaceState({}, '', p.show_url);
        } catch (e) {}
    }

    // Click en fila
    if (payList) {
        payList.querySelectorAll('.list-group-item').forEach(function (it) {
            it.addEventListener('click', function () {
                const id = this.getAttribute('data-pay-id');
                renderDetail(findPay(id));
            });
        });
    }

    // Inicial
    renderDetail(findPay(selectedId) || payload[0]);
})();
</script>
@endsection

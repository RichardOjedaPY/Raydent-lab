 {{-- resources/views/admin/pedidos/liquidar.blade.php --}}
@extends('layouts.admin')

@section('title', 'Liquidar Pedido')
@section('content_header', 'Liquidar Pedido')

@section('content')
@php
    $fmtGs = fn($n) => number_format((int)$n, 0, ',', '.');

    // Keys ya cargadas (si existe liquidación)
    $keysCargados = $liq ? $liq->items->pluck('concept_key')->values()->all() : [];

    // Separar: cargados vs pendientes (sin tocar el controlador)
    $cargados = collect($viewItems ?? [])->filter(fn($it) => in_array($it['key'], $keysCargados, true))->values();
    $pendientes = collect($viewItems ?? [])->filter(fn($it) => !in_array($it['key'], $keysCargados, true))->values();

    // Normalizar grupo
    $groupName = function($g){
        $g = trim((string)$g);
        return $g !== '' ? $g : 'Otros';
    };

    // Agrupar para SELECT (pendientes)
    $pendientesGrouped = $pendientes
        ->sortBy(fn($it) => $groupName($it['grupo'] ?? ''))
        ->groupBy(fn($it) => $groupName($it['grupo'] ?? ''));

    // Agrupar para TABLA (cargados)
    $cargadosGrouped = $cargados
        ->sortBy(fn($it) => $groupName($it['grupo'] ?? ''))
        ->groupBy(fn($it) => $groupName($it['grupo'] ?? ''));

@endphp

<style>
    /* --- Mejor legibilidad / menos “encimado” --- */
    .liq-card .card-body { padding: 0; }
    .liq-topbar { padding: 1rem; border-bottom: 1px solid rgba(0,0,0,.08); }

    .liq-table th { white-space: nowrap; }
    .liq-table td { vertical-align: middle; }

    .col-concepto { min-width: 340px; }
    .col-obs      { min-width: 280px; }
    .col-cant     { width: 90px; }
    .col-base     { width: 140px; }
    .col-precio   { width: 160px; }
    .col-sub      { width: 140px; }
    .col-act      { width: 90px; }

    .table-group-row td{
        background: #f1f5f9;
        font-weight: 700;
        text-transform: uppercase;
        font-size: .75rem;
        letter-spacing: .02em;
        border-top: 1px solid rgba(0,0,0,.08);
    }

    .liq-table .form-control-sm{ min-height: 34px; }

    /* En pantallas chicas, que no “aplasten” */
    @media (max-width: 767.98px){
        .liq-topbar { padding: .75rem; }
        .col-concepto { min-width: 280px; }
        .col-obs      { min-width: 240px; }
    }
</style>

{{-- Header --}}
<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex flex-wrap align-items-center justify-content-between">
            <div>
                <div class="h5 mb-1">
                    Pedido: <span class="text-primary">{{ $pedido->codigo_pedido ?? $pedido->id }}</span>
                </div>
                <div class="text-muted">
                    Clínica: <strong>{{ $pedido->clinica->nombre ?? '—' }}</strong>
                    · Paciente: <strong>{{ $pedido->paciente->apellido ?? '' }} {{ $pedido->paciente->nombre ?? '' }}</strong>
                    · Estado: <span class="badge badge-secondary">{{ $pedido->estado }}</span>
                </div>
            </div>

            <div class="mt-2 mt-md-0">
                <a href="{{ route('admin.pedidos.show', $pedido) }}" class="btn btn-outline-secondary">
                    Volver
                </a>
            </div>
        </div>
    </div>
</div>

<form method="post" action="{{ route('admin.pedidos.liquidar.update', $pedido) }}" id="frm-liquidacion">
    @csrf

    {{-- 1) Resumen arriba --}}
    <div class="card mb-3">
        <div class="card-header">
            <strong>Resumen del pedido</strong>
        </div>

        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-3 mb-md-0">
                    <div class="small text-muted">Prioridad</div>
                    <div class="font-weight-bold">{{ $pedido->prioridad ?? 'normal' }}</div>
                </div>

                <div class="col-md-4 mb-3 mb-md-0">
                    <div class="small text-muted">Agendado</div>
                    <div class="font-weight-bold">
                        {{ $pedido->fecha_agendada ? $pedido->fecha_agendada->format('d/m/Y') : '—' }}
                        {{ $pedido->hora_agendada ? $pedido->hora_agendada->format('H:i') : '' }}
                    </div>
                </div>

                <div class="col-md-5">
                    <div class="small text-muted">Selecciones</div>
                    <div class="text-muted small">
                        La clínica ya marcó estos conceptos. Abajo agregás a la liquidación (sin duplicar).
                    </div>
                    <ul class="mb-0 mt-2">
                        <li>Fotos solicitadas: {{ $pedido->fotos->count() }}</li>
                        <li>Cefalometrías: {{ $pedido->cefalometrias->count() }}</li>
                        <li>Piezas: {{ $pedido->piezas->count() }}</li>
                    </ul>
                </div>
            </div>

            <hr>

            <div class="small text-muted mb-1">Descripción del caso</div>
            <div>{{ $pedido->descripcion_caso ?: '—' }}</div>

            @if (empty($viewItems) || count($viewItems) === 0)
                <div class="alert alert-warning mt-3 mb-0">
                    Este pedido no tiene conceptos cobrables detectados.
                    Revisá si la clínica marcó estudios/fotos/cefalometrías/piezas o si el pedido aún no tiene selecciones guardadas.
                </div>
            @endif
        </div>
    </div>

    {{-- 2) Liquidación abajo --}}
    <div class="card liq-card mb-5">
        <div class="card-header d-flex align-items-center justify-content-between">
            <strong>Liquidación (Gs.)</strong>
            <span class="text-muted small">Agregá conceptos pendientes, cargá precios y guardá.</span>
        </div>

        {{-- Barra superior: selector + botones --}}
        <div class="liq-topbar">
            <div class="row align-items-end">
                <div class="col-lg-8">
                    <label class="small text-muted mb-1">
                        Concepto pendiente (solo lo marcado por la clínica)
                    </label>

                    <select id="sel-concepto" class="form-control">
                        <option value="">— Seleccione —</option>

                        @foreach($pendientesGrouped as $grupo => $itemsGrupo)
                            <optgroup label="{{ $grupo }}">
                                @foreach($itemsGrupo as $it)
                                    @php
                                        $base  = (int)($it['precio_base_gs'] ?? 0);
                                        $label = $it['label'] ?? $it['key'];
                                    @endphp
                                    <option value="{{ $it['key'] }}">
                                        {{ $label }} — Base: {{ $fmtGs($base) }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>

                    <div class="small text-muted mt-2">
                        Pendientes: <strong id="pend-count">{{ $pendientes->count() }}</strong>
                        · Cargados: <strong id="carg-count">{{ $cargados->count() }}</strong>
                    </div>
                </div>

                <div class="col-lg-4 mt-2 mt-lg-0">
                    <div class="d-flex">
                        <button type="button" id="btn-add" class="btn btn-outline-primary flex-fill mr-2">
                            Agregar
                        </button>
                        <button type="button" id="btn-add-all" class="btn btn-outline-secondary flex-fill">
                            Agregar todos
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabla --}}
        <div class="table-responsive">
            <table class="table table-sm mb-0 liq-table" id="tbl-liquidacion">
                <thead class="thead-light">
                    <tr>
                        <th class="col-concepto">Concepto</th>
                        <th class="col-obs">Observación</th>
                        <th class="text-center col-cant">Cant.</th>
                        <th class="text-right col-base">Base</th>
                        <th class="text-right col-precio">Precio</th>
                        <th class="text-right col-sub">Subtotal</th>
                        <th class="text-center col-act">Acción</th>
                    </tr>
                </thead>

                <tbody id="tbody-liq">
                    @php $idx = 0; @endphp

                    @if($cargados->count() === 0)
                        <tr id="row-empty">
                            <td colspan="7" class="text-center text-muted py-4">
                                Aún no agregaste conceptos a la liquidación. Usá el selector de arriba.
                            </td>
                        </tr>
                    @endif

                    @foreach($cargadosGrouped as $grupo => $itemsGrupo)
                        <tr class="table-group-row" data-group="{{ $grupo }}">
                            <td colspan="7">{{ $grupo }}</td>
                        </tr>

                        @foreach($itemsGrupo as $it)
                            <tr data-key="{{ $it['key'] }}" data-group="{{ $grupo }}">
                                <td class="col-concepto">
                                    <div class="font-weight-bold">{{ $it['label'] }}</div>
                                    <div class="small text-muted">{{ $grupo }}</div>

                                    <input type="hidden" name="items[{{ $idx }}][key]" value="{{ $it['key'] }}">
                                    <input type="hidden" name="items[{{ $idx }}][label]" value="{{ $it['label'] }}">
                                    <input type="hidden" name="items[{{ $idx }}][grupo]" value="{{ $it['grupo'] }}">
                                    <input type="hidden" name="items[{{ $idx }}][orden]" value="{{ $it['orden'] }}">
                                </td>

                                <td class="col-obs">
                                    <input type="text"
                                           class="form-control form-control-sm"
                                           name="items[{{ $idx }}][observacion]"
                                           value="{{ old("items.$idx.observacion", $it['observacion']) }}"
                                           placeholder="Opcional">
                                </td>

                                <td class="text-center col-cant">
                                    <input type="number"
                                           class="form-control form-control-sm text-center js-qty"
                                           name="items[{{ $idx }}][cantidad]"
                                           value="{{ old("items.$idx.cantidad", $it['cantidad']) }}"
                                           min="1">
                                </td>

                                <td class="text-right col-base">
                                    <span class="text-muted js-base-txt">{{ $fmtGs($it['precio_base_gs']) }}</span>
                                    <input type="hidden" name="items[{{ $idx }}][precio_base_gs]" value="{{ $it['precio_base_gs'] }}">
                                </td>

                                <td class="col-precio">
                                    <input type="text"
                                           class="form-control form-control-sm text-right js-gs js-precio"
                                           name="items[{{ $idx }}][precio_final_gs]"
                                           value="{{ old("items.$idx.precio_final_gs", $fmtGs($it['precio_final_gs'])) }}"
                                           inputmode="numeric"
                                           placeholder="0">
                                </td>

                                <td class="text-right col-sub">
                                    <span class="js-subtotal text-dark font-weight-bold">0</span>
                                </td>

                                <td class="text-center col-act">
                                    <button type="button" class="btn btn-sm btn-outline-danger js-remove">
                                        Quitar
                                    </button>
                                </td>
                            </tr>
                            @php $idx++; @endphp
                        @endforeach
                    @endforeach
                </tbody>

                <tfoot class="thead-light">
                    <tr>
                        <th colspan="5" class="text-right">TOTAL</th>
                        <th class="text-right">
                            <span id="tot-gs" class="font-weight-bold">0</span>
                        </th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="card-footer d-flex justify-content-between">
            <a href="{{ route('admin.pedidos.show', $pedido) }}" class="btn btn-outline-secondary">
                Cancelar
            </a>
            <button type="submit" class="btn btn-primary">
                Guardar liquidación
            </button>
        </div>
    </div>
</form>

{{-- Template de fila (para agregar desde el selector) --}}
<template id="tpl-row">
    <tr data-key="__KEY__" data-group="__GRUPO__">
        <td class="col-concepto">
            <div class="font-weight-bold">__LABEL__</div>
            <div class="small text-muted">__GRUPO__</div>

            <input type="hidden" name="items[__I__][key]" value="__KEY__">
            <input type="hidden" name="items[__I__][label]" value="__LABEL__">
            <input type="hidden" name="items[__I__][grupo]" value="__GRUPO_RAW__">
            <input type="hidden" name="items[__I__][orden]" value="__ORDEN__">
        </td>

        <td class="col-obs">
            <input type="text"
                   class="form-control form-control-sm"
                   name="items[__I__][observacion]"
                   value="__OBS__"
                   placeholder="Opcional">
        </td>

        <td class="text-center col-cant">
            <input type="number"
                   class="form-control form-control-sm text-center js-qty"
                   name="items[__I__][cantidad]"
                   value="__QTY__"
                   min="1">
        </td>

        <td class="text-right col-base">
            <span class="text-muted js-base-txt">__BASE_TXT__</span>
            <input type="hidden" name="items[__I__][precio_base_gs]" value="__BASE__">
        </td>

        <td class="col-precio">
            <input type="text"
                   class="form-control form-control-sm text-right js-gs js-precio"
                   name="items[__I__][precio_final_gs]"
                   value="__PRICE_TXT__"
                   inputmode="numeric"
                   placeholder="0">
        </td>

        <td class="text-right col-sub">
            <span class="js-subtotal text-dark font-weight-bold">0</span>
        </td>

        <td class="text-center col-act">
            <button type="button" class="btn btn-sm btn-outline-danger js-remove">
                Quitar
            </button>
        </td>
    </tr>
</template>

<script>
(function(){
    // --- helpers Gs ---
    function onlyDigits(s){ return String(s || '').replace(/\D+/g, ''); }

    function formatGs(value){
        const digits = onlyDigits(value);
        if(!digits) return '';
        return digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function parseGs(value){
        const digits = onlyDigits(value);
        return digits ? parseInt(digits, 10) : 0;
    }

    function cssEscape(str){
        str = String(str || '');
        if(window.CSS && CSS.escape) return CSS.escape(str);
        return str.replace(/[^a-zA-Z0-9_\-]/g, '\\$&');
    }

    // --- data ---
    const all = @json(collect($viewItems ?? [])->values());
    const map = new Map(all.map(it => [it.key, it]));

    const tbody = document.getElementById('tbody-liq');
    const sel = document.getElementById('sel-concepto');
    const btnAdd = document.getElementById('btn-add');
    const btnAddAll = document.getElementById('btn-add-all');
    const tpl = document.getElementById('tpl-row');

    const pendCount = document.getElementById('pend-count');
    const cargCount = document.getElementById('carg-count');
    const emptyRow = document.getElementById('row-empty');

    // índice inicial = cantidad de filas existentes renderizadas
    let nextIndex = (function(){
        const inputs = document.querySelectorAll('#tbl-liquidacion tbody input[name^="items["][name$="[key]"]');
        return inputs.length;
    })();

    function groupName(g){
        g = String(g || '').trim();
        return g !== '' ? g : 'Otros';
    }

    function pendingOptions(){
        if(!sel) return [];
        return Array.from(sel.querySelectorAll('option[value]'))
            .filter(o => String(o.value || '').trim() !== '');
    }

    function updateCounts(){
        if(pendCount) pendCount.textContent = pendingOptions().length;
        if(cargCount) cargCount.textContent = tbody.querySelectorAll('tr[data-key]').length;
    }

    function ensureEmptyHiddenRow(){
        const hasRows = tbody.querySelectorAll('tr[data-key]').length > 0;
        if(emptyRow){
            emptyRow.style.display = hasRows ? 'none' : '';
        }
    }

    function ensureGroupHeader(grupo){
        const q = 'tr.table-group-row[data-group="' + cssEscape(grupo) + '"]';
        let hdr = tbody.querySelector(q);
        if(hdr) return hdr;

        hdr = document.createElement('tr');
        hdr.className = 'table-group-row';
        hdr.setAttribute('data-group', grupo);

        const td = document.createElement('td');
        td.colSpan = 7;
        td.textContent = grupo;

        hdr.appendChild(td);
        tbody.appendChild(hdr);
        return hdr;
    }

    function lastRowInGroup(grupo){
        const rows = Array.from(tbody.querySelectorAll('tr[data-key][data-group="' + cssEscape(grupo) + '"]'));
        return rows.length ? rows[rows.length - 1] : null;
    }

    function recalc(){
        const rows = document.querySelectorAll('#tbl-liquidacion tbody tr[data-key]');
        let total = 0;

        rows.forEach(tr => {
            const qtyInp = tr.querySelector('.js-qty');
            const priceInp = tr.querySelector('.js-precio');
            const subEl = tr.querySelector('.js-subtotal');

            const qty = qtyInp ? parseInt(qtyInp.value || '1', 10) : 1;
            const price = priceInp ? parseGs(priceInp.value) : 0;

            const sub = (qty > 0 ? qty : 1) * price;
            total += sub;

            if(subEl){
                subEl.textContent = formatGs(sub) || '0';
            }
        });

        const totEl = document.getElementById('tot-gs');
        if(totEl) totEl.textContent = formatGs(total) || '0';
    }

    function removeOptionByKey(key){
        if(!sel) return;
        const opt = sel.querySelector('option[value="' + cssEscape(key) + '"]');
        if(opt) opt.remove();

        // si el optgroup queda vacío (sin options), eliminarlo
        Array.from(sel.querySelectorAll('optgroup')).forEach(og => {
            if(og.querySelectorAll('option').length === 0) og.remove();
        });
    }

    function addOptionBack(key){
        if(!sel) return;
        if(sel.querySelector('option[value="' + cssEscape(key) + '"]')) return;

        const it = map.get(key);
        if(!it) return;

        const grupo = groupName(it.grupo);
        const base = formatGs(it.precio_base_gs || 0) || '0';
        const label = (it.label || it.key || '').trim();

        // buscar/crear optgroup
        let og = Array.from(sel.querySelectorAll('optgroup'))
            .find(x => String(x.label || '').trim() === grupo);

        if(!og){
            og = document.createElement('optgroup');
            og.label = grupo;
            sel.appendChild(og);
        }

        const opt = document.createElement('option');
        opt.value = key;
        opt.textContent = label + ' — Base: ' + base;

        og.appendChild(opt);
    }

    function addRowByKey(key){
        key = String(key || '').trim();
        if(!key) return;

        const it = map.get(key);
        if(!it) return;

        // evitar duplicado
        if(tbody.querySelector('tr[data-key="' + cssEscape(key) + '"]')) return;

        const grupo = groupName(it.grupo);
        const base = parseInt(it.precio_base_gs || 0, 10) || 0;
        const price = parseInt(it.precio_final_gs || base, 10) || 0;
        const qty = parseInt(it.cantidad || 1, 10) || 1;
        const obs = (it.observacion || '').toString();

        // crear header si no existe
        const hdr = ensureGroupHeader(grupo);

        // template -> html
        let html = tpl.innerHTML;
        html = html
            .replaceAll('__I__', String(nextIndex))
            .replaceAll('__KEY__', key)
            .replaceAll('__LABEL__', (it.label || key))
            .replaceAll('__GRUPO__', grupo)
            .replaceAll('__GRUPO_RAW__', (it.grupo || grupo))
            .replaceAll('__ORDEN__', String(it.orden || nextIndex))
            .replaceAll('__OBS__', obs.replaceAll('"','&quot;'))
            .replaceAll('__QTY__', String(qty))
            .replaceAll('__BASE__', String(base))
            .replaceAll('__BASE_TXT__', formatGs(base) || '0')
            .replaceAll('__PRICE_TXT__', formatGs(price) || '0');

        const tmp = document.createElement('tbody');
        tmp.innerHTML = html.trim();
        const tr = tmp.firstElementChild;

        // insertar en su grupo (después del último row del grupo, o después del header)
        const last = lastRowInGroup(grupo);
        if(last){
            last.insertAdjacentElement('afterend', tr);
        }else{
            hdr.insertAdjacentElement('afterend', tr);
        }

        nextIndex++;

        // sacar del select
        removeOptionByKey(key);
        if(sel) sel.value = '';

        ensureEmptyHiddenRow();
        recalc();
        updateCounts();
    }

    function cleanupGroupIfEmpty(grupo){
        const hasRows = tbody.querySelectorAll('tr[data-key][data-group="' + cssEscape(grupo) + '"]').length > 0;
        if(hasRows) return;

        const hdr = tbody.querySelector('tr.table-group-row[data-group="' + cssEscape(grupo) + '"]');
        if(hdr) hdr.remove();
    }

    // --- events ---
    if(btnAdd){
        btnAdd.addEventListener('click', function(){
            if(!sel) return;
            const key = sel.value;
            if(!key) return;
            addRowByKey(key);
        });
    }

    if(btnAddAll){
        btnAddAll.addEventListener('click', function(){
            const keys = pendingOptions().map(o => o.value);
            keys.forEach(k => addRowByKey(k));
        });
    }

    document.addEventListener('click', function(e){
        const btn = e.target.closest('.js-remove');
        if(!btn) return;

        const tr = btn.closest('tr[data-key]');
        if(!tr) return;

        const key = tr.getAttribute('data-key');
        const grupo = tr.getAttribute('data-group') || 'Otros';

        tr.remove();

        // vuelve al selector
        addOptionBack(key);

        // si el grupo quedó vacío, borrar header
        cleanupGroupIfEmpty(grupo);

        ensureEmptyHiddenRow();
        recalc();
        updateCounts();
    });

    document.addEventListener('input', function(e){
        if(e.target && e.target.classList.contains('js-gs')){
            e.target.value = formatGs(e.target.value);
        }
        if(e.target && (e.target.classList.contains('js-precio') || e.target.classList.contains('js-qty'))){
            recalc();
        }
    });

    // init
    ensureEmptyHiddenRow();
    recalc();
    updateCounts();
})();
</script>
@endsection

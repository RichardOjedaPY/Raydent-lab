@extends('layouts.admin')

@section('title', 'Tarifario Maestro')
@section('content_header', 'Tarifario Maestro')

@section('content')
@php
    $fmtGs = fn($n) => number_format((int)$n, 0, ',', '.');
@endphp

<style>
    .tarifario-card {
        border-radius: .75rem;
        overflow: hidden;
        box-shadow: 0 10px 25px rgba(15, 23, 42, .06);
        border: 1px solid rgba(148, 163, 184, .35);
    }
    .tarifario-toolbar {
        background: linear-gradient(180deg, rgba(248,250,252,1) 0%, rgba(255,255,255,1) 100%);
        border-bottom: 1px solid rgba(148, 163, 184, .25);
    }
    .tarifario-scroll {
        max-height: clamp(360px, 64vh, 760px);
        overflow: auto;
    }
    .tarifario-table thead th {
        position: sticky;
        top: 0;
        z-index: 3;
        background: #f8fafc;
        border-bottom: 1px solid rgba(148, 163, 184, .35);
        font-weight: 700;
    }
    .tarifario-table td, .tarifario-table th { vertical-align: middle !important; }
    .mono {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        font-size: .86rem;
    }
    .row-inactive { opacity: .55; }
    .badge-soft {
        background: rgba(59, 130, 246, .08);
        color: #1d4ed8;
        border: 1px solid rgba(59, 130, 246, .18);
        font-weight: 600;
        padding: .25rem .5rem;
        border-radius: 999px;
    }
    .price-input { min-width: 170px; }
    .savebar {
        position: sticky;
        bottom: 0;
        z-index: 5;
        background: rgba(255,255,255,.92);
        backdrop-filter: blur(6px);
        border-top: 1px solid rgba(148, 163, 184, .25);
    }
    @media (max-width: 768px){
        .tarifario-scroll { max-height: none; }
        .price-input { min-width: 140px; }
    }
</style>

{{-- Flash --}}
@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

{{-- =========================
|  FILTROS
========================= --}}
<div class="card tarifario-card mb-3">
    <div class="card-body">
        <form method="get" class="row">
            <div class="col-lg-6 mb-2">
                <label class="small text-muted mb-1">Buscar</label>
                <div class="input-group">
                    <input type="text"
                           name="q"
                           value="{{ request('q') }}"
                           class="form-control"
                           placeholder="Nombre, grupo o key">

                    <div class="input-group-append">
                        <button class="btn btn-outline-primary" type="submit" title="Buscar">
                            <i class="fas fa-search"></i>
                        </button>
                        <a class="btn btn-outline-secondary"
                           href="{{ route('admin.tarifario.index') }}"
                           title="Limpiar filtros">
                            Limpiar
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 mb-2">
                <label class="small text-muted mb-1">Grupo</label>
                <select name="grupo" class="form-control">
                    <option value="">— Todos —</option>
                    @foreach($grupos as $g)
                        <option value="{{ $g }}" @selected(request('grupo')===$g)>{{ $g }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-lg-2 mb-2 d-flex align-items-end">
                <button class="btn btn-primary w-100" type="submit">
                    <i class="fas fa-filter mr-1"></i> Filtrar
                </button>
            </div>
        </form>
    </div>
</div>

{{-- =========================
|  TABLA / UPDATE
========================= --}}
<form method="post" action="{{ route('admin.tarifario.update') }}" id="frm-tarifario">
    @csrf

    <div class="card tarifario-card">
        {{-- Header --}}
        <div class="card-header tarifario-toolbar d-flex flex-wrap align-items-center justify-content-between">
            <div>
                <div class="h6 mb-0">Precios base (global)</div>
                <div class="small text-muted">
                    Se usa como referencia para todas las clínicas. Si luego querés, una clínica puede tener override puntual.
                </div>
            </div>

            <div class="mt-2 mt-md-0 d-flex align-items-center" style="gap:.5rem;">
                <div class="text-muted small d-none d-md-block">
                    Mostrando
                    <strong>{{ $conceptos->firstItem() ?? 0 }}</strong>–
                    <strong>{{ $conceptos->lastItem() ?? 0 }}</strong>
                    de <strong>{{ $conceptos->total() }}</strong>
                </div>

                <button class="btn btn-primary btn-sm" type="submit">
                    <i class="fas fa-save mr-1"></i> Guardar cambios
                </button>
            </div>
        </div>

        {{-- Table --}}
        <div class="card-body p-0">
            <div class="tarifario-scroll">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0 tarifario-table">
                        <thead>
                            <tr>
                                <th style="min-width: 320px;">Concepto</th>
                                <th style="min-width: 160px;">Grupo</th>
                                <th style="min-width: 280px;">Key</th>
                                <th class="text-right" style="width: 240px;">Precio base (Gs.)</th>
                                <th class="text-center" style="width: 120px;">Activo</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($conceptos as $i => $c)
                                <tr class="{{ !$c->is_active ? 'row-inactive' : '' }}" data-row>
                                    <td>
                                        <div class="font-weight-bold">{{ $c->nombre }}</div>
                                        @if(!$c->is_active)
                                            <div class="small text-muted">Inactivo</div>
                                        @endif
                                    </td>

                                    <td>
                                        @if($c->grupo)
                                            <span class="badge-soft">{{ $c->grupo }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>

                                    <td class="mono text-muted">{{ $c->concept_key }}</td>

                                    <td class="text-right">
                                        <input type="hidden" name="items[{{ $i }}][concept_key]" value="{{ $c->concept_key }}">

                                        <div class="input-group input-group-sm">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">Gs</span>
                                            </div>
                                            <input type="text"
                                                   class="form-control text-right js-gs price-input"
                                                   name="items[{{ $i }}][precio_gs]"
                                                   value="{{ $fmtGs($c->precio_gs) }}"
                                                   inputmode="numeric"
                                                   placeholder="0">
                                        </div>
                                    </td>

                                    <td class="text-center">
                                        {{-- Para que siempre mande 0/1 --}}
                                        <input type="hidden" name="items[{{ $i }}][is_active]" value="0">

                                        <div class="custom-control custom-switch d-inline-block">
                                            <input type="checkbox"
                                                   class="custom-control-input js-active"
                                                   id="sw-{{ $c->id }}"
                                                   name="items[{{ $i }}][is_active]"
                                                   value="1"
                                                   @checked((bool)$c->is_active)>
                                            <label class="custom-control-label" for="sw-{{ $c->id }}"></label>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-5">
                                        No hay conceptos en el tarifario.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Save bar inferior (mobile + UX) --}}
            <div class="savebar p-2 d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    Total: <strong>{{ $conceptos->total() }}</strong>
                </div>
                <button class="btn btn-primary btn-sm" type="submit">
                    <i class="fas fa-save mr-1"></i> Guardar
                </button>
            </div>
        </div>

        {{-- Footer --}}
        <div class="card-footer d-flex flex-wrap justify-content-between align-items-center">
            <div class="text-muted small mb-2 mb-md-0">
                Mostrando
                <strong>{{ $conceptos->firstItem() ?? 0 }}</strong>–
                <strong>{{ $conceptos->lastItem() ?? 0 }}</strong>
                de <strong>{{ $conceptos->total() }}</strong>
            </div>

            <div>
                {{ $conceptos->appends(request()->query())->links('pagination::bootstrap-4') }}
            </div>
        </div>
    </div>
</form>

<script>
(function(){
    function onlyDigits(s){ return String(s || '').replace(/\D+/g, ''); }
    function formatGs(value){
        const digits = onlyDigits(value);
        if(!digits) return '';
        return digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    // Formato Gs + mini feedback visual cuando editás
    document.addEventListener('input', function(e){
        const el = e.target;

        if(el && el.classList.contains('js-gs')){
            el.value = formatGs(el.value);

            const tr = el.closest('tr[data-row]');
            if(tr){
                tr.classList.add('table-warning');
                clearTimeout(tr._t);
                tr._t = setTimeout(() => tr.classList.remove('table-warning'), 900);
            }
        }
    });

    document.addEventListener('change', function(e){
        const el = e.target;
        if(el && el.classList.contains('js-active')){
            const tr = el.closest('tr[data-row]');
            if(tr){
                tr.classList.add('table-warning');
                clearTimeout(tr._t2);
                tr._t2 = setTimeout(() => tr.classList.remove('table-warning'), 900);
            }
        }
    });
})();
</script>
@endsection

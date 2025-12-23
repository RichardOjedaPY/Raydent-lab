@extends('layouts.admin')

@section('title', 'Tarifario por Clínica')
@section('content_header', 'Tarifario por Clínica')

@section('content')
@php
    $fmtGs = fn($n) => number_format((int)$n, 0, ',', '.');
@endphp

<style>
    /* Estilos Modernos */
    .raydent-card {
        border-radius: 12px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        border: 1px solid #e9ecef;
    }
    .select-clinica-wrapper {
        background: #f8fafc;
        border-radius: 10px;
        padding: 15px;
        border: 1px solid #e2e8f0;
    }
    .table thead th {
        background: #f1f5f9;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.05em;
        color: #64748b;
        border-top: none;
        position: sticky;
        top: 0;
        z-index: 10;
    }
    .soft-badge {
        background: #e2e8f0;
        color: #475569;
        padding: 2px 8px;
        border-radius: 6px;
        font-size: 0.85rem;
        font-weight: 500;
    }
    .price-input-wrapper .input-group-text {
        background: transparent;
        border-right: none;
        color: #94a3b8;
    }
    .price-input-wrapper .form-control {
        border-left: none;
        font-weight: 600;
        color: #1e293b;
    }
    .price-input-wrapper .form-control:focus {
        background: #fff;
    }
    .base-price {
        color: #94a3b8;
        font-size: 0.9rem;
    }
    .effective-price {
        color: #0f172a;
        font-size: 1rem;
    }
    .row-has-override {
        background-color: rgba(59, 130, 246, 0.03);
    }
    .sticky-footer-btn {
        position: sticky;
        bottom: 20px;
        z-index: 100;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }
</style>

{{-- Header de Selección --}}
<div class="card raydent-card mb-4">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-7">
                <div class="select-clinica-wrapper">
                    <label class="small font-weight-bold text-uppercase text-primary mb-2 d-block">Configurando Precios para:</label>
                    <select class="form-control form-control-lg border-0 bg-transparent p-0 font-weight-bold" id="sel-clinica" style="box-shadow: none; font-size: 1.2rem;">
                        @foreach($clinicas as $c)
                            <option value="{{ route('admin.tarifario.clinica', $c->id) }}" @selected($c->id === $clinica->id)>
                                🏢 {{ $c->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <p class="small text-muted mt-2 mb-0">
                    <i class="fas fa-info-circle mr-1"></i> Los campos vacíos heredarán automáticamente el <strong>Precio Base Global</strong>.
                </p>
            </div>
            <div class="col-md-5 text-md-right mt-3 mt-md-0">
                <a class="btn btn-link text-muted mr-3" href="{{ route('admin.tarifario.index') }}">
                    <i class="fas fa-arrow-left mr-1"></i> Ver Maestro
                </a>
                <button type="submit" form="main-form" class="btn btn-primary px-4 shadow-sm">
                    <i class="fas fa-save mr-1"></i> Guardar Cambios
                </button>
            </div>
        </div>
    </div>
</div>

<form method="post" action="{{ route('admin.tarifario.clinica.update', $clinica) }}" id="main-form">
    @csrf

    <div class="card raydent-card">
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 70vh;">
                <table class="table table-hover align-middle mb-0" id="tbl-clinica">
                    <thead>
                        <tr>
                            <th class="pl-4">Concepto / Servicio</th>
                            <th>Grupo</th>
                            <th class="text-right">Base Global</th>
                            <th class="text-right" style="width:220px;">Override Clínica</th>
                            <th class="text-right pr-4">Precio Final</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($conceptos as $i => $c)
                            @php
                                $ov = $overrides->get($c->concept_key);
                                $base = (int)$c->precio_gs;
                                $ovGs = $ov ? (int)$ov->precio_gs : null;
                                $hasOverride = $ov !== null;
                                $efectivo = $hasOverride ? $ovGs : $base;
                            @endphp
                            <tr class="{{ $hasOverride ? 'row-has-override' : '' }}">
                                <td class="pl-4">
                                    <div class="font-weight-bold text-dark">{{ $c->nombre }}</div>
                                    <code class="small text-muted">{{ $c->concept_key }}</code>
                                    <input type="hidden" name="items[{{ $i }}][concept_key]" value="{{ $c->concept_key }}">
                                </td>
                                <td>
                                    <span class="soft-badge">{{ $c->grupo ?: 'General' }}</span>
                                </td>
                                <td class="text-right align-middle">
                                    <span class="base-price">Gs. {{ $fmtGs($base) }}</span>
                                    <input type="hidden" class="js-base" value="{{ $base }}">
                                </td>
                                <td>
                                    <div class="input-group input-group-sm price-input-wrapper">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text small">Gs.</span>
                                        </div>
                                        <input type="text"
                                            class="form-control text-right js-gs js-override"
                                            name="items[{{ $i }}][precio_override_gs]"
                                            value="{{ $ov ? $fmtGs($ovGs) : '' }}"
                                            inputmode="numeric"
                                            placeholder="Usar base">
                                    </div>
                                </td>
                                <td class="text-right align-middle pr-4">
                                    <span class="font-weight-bold effective-price js-efectivo">
                                        {{ $fmtGs($efectivo) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="text-right mt-4 pb-5">
        <button class="btn btn-primary btn-lg px-5 shadow sticky-footer-btn" type="submit">
            <i class="fas fa-save mr-2"></i> Guardar Tarifario
        </button>
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
    function parseGs(value){
        const digits = onlyDigits(value);
        return digits ? parseInt(digits, 10) : 0;
    }

    function recalcRow(tr){
        const base = parseInt(tr.querySelector('.js-base')?.value || '0', 10) || 0;
        const ovInp = tr.querySelector('.js-override');
        const efectivoEl = tr.querySelector('.js-efectivo');

        const ovValueRaw = onlyDigits(ovInp.value);
        const hasValue = ovValueRaw.length > 0;
        
        const effective = hasValue ? parseInt(ovValueRaw, 10) : base;

        if(efectivoEl) efectivoEl.textContent = formatGs(effective) || '0';
        
        // Feedback visual: resaltar si hay un cambio manual
        if(hasValue) {
            tr.classList.add('row-has-override');
            efectivoEl.classList.add('text-primary');
        } else {
            tr.classList.remove('row-has-override');
            efectivoEl.classList.remove('text-primary');
        }
    }

    document.addEventListener('input', function(e){
        if(e.target && e.target.classList.contains('js-gs')){
            e.target.value = formatGs(e.target.value);
            const tr = e.target.closest('tr');
            if(tr) recalcRow(tr);
        }
    });

    // Inicializar al cargar
    document.querySelectorAll('#tbl-clinica tbody tr').forEach(tr => recalcRow(tr));

    const sel = document.getElementById('sel-clinica');
    if(sel){
        sel.addEventListener('change', function(){
            if(this.value) {
                // Loader sencillo
                document.body.style.opacity = '0.5';
                window.location.href = this.value;
            }
        });
    }
})();
</script>
@endsection
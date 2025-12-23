@extends('layouts.admin')

@section('title', 'Tarifario por Clínica')
@section('content_header', 'Tarifario por Clínica')

@section('content')
@php
    $fmtGs = fn($n) => number_format((int)$n, 0, ',', '.');
@endphp

<div class="card mb-3">
    <div class="card-body">
        <div class="row align-items-end">
            <div class="col-md-8">
                <label class="small text-muted mb-1">Clínica</label>
                <select class="form-control" id="sel-clinica">
                    @foreach($clinicas as $c)
                        <option value="{{ route('admin.tarifario.clinica', $c->id) }}" @selected($c->id === $clinica->id)>
                            {{ $c->nombre }}
                        </option>
                    @endforeach
                </select>
                <div class="small text-muted mt-1">
                    Si dejás un override vacío, se usará el precio base global.
                </div>
            </div>
            <div class="col-md-4 mt-2 mt-md-0">
                <a class="btn btn-outline-secondary w-100" href="{{ route('admin.tarifario.index') }}">
                    Ver tarifario maestro
                </a>
            </div>
        </div>
    </div>
</div>

<form method="post" action="{{ route('admin.tarifario.clinica.update', $clinica) }}">
    @csrf

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <strong>Precios para: {{ $clinica->nombre }}</strong>
            <button class="btn btn-primary btn-sm" type="submit">Guardar</button>
        </div>

        <div class="card-body p-0 table-responsive">
            <table class="table table-sm mb-0" id="tbl-clinica">
                <thead class="thead-light">
                    <tr>
                        <th style="min-width:260px;">Concepto</th>
                        <th style="min-width:160px;">Grupo</th>
                        <th class="text-right" style="width:170px;">Base global</th>
                        <th class="text-right" style="width:200px;">Override clínica</th>
                        <th class="text-right" style="width:170px;">Efectivo</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($conceptos as $i => $c)
                        @php
                            $ov = $overrides->get($c->concept_key);
                            $base = (int)$c->precio_gs;
                            $ovGs = $ov ? (int)$ov->precio_gs : null;
                            $efectivo = $ov ? $ovGs : $base;
                        @endphp
                        <tr>
                            <td>
                                <div class="font-weight-bold">{{ $c->nombre }}</div>
                                <div class="small text-muted text-monospace">{{ $c->concept_key }}</div>
                                <input type="hidden" name="items[{{ $i }}][concept_key]" value="{{ $c->concept_key }}">
                            </td>
                            <td class="text-muted">{{ $c->grupo ?: '—' }}</td>

                            <td class="text-right align-middle">
                                <span class="text-muted">{{ $fmtGs($base) }}</span>
                                <input type="hidden" class="js-base" value="{{ $base }}">
                            </td>

                            <td>
                                <input type="text"
                                    class="form-control form-control-sm text-right js-gs js-override"
                                    name="items[{{ $i }}][precio_override_gs]"
                                    value="{{ $ov ? $fmtGs($ovGs) : '' }}"
                                    inputmode="numeric"
                                    placeholder="(vacío = usar base)">
                            </td>

                            <td class="text-right align-middle">
                                <span class="font-weight-bold js-efectivo">{{ $fmtGs($efectivo) }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="card-footer d-flex justify-content-end">
            <button class="btn btn-primary" type="submit">Guardar</button>
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
    function parseGs(value){
        const digits = onlyDigits(value);
        return digits ? parseInt(digits, 10) : 0;
    }

    function recalcRow(tr){
        const base = parseInt(tr.querySelector('.js-base')?.value || '0', 10) || 0;
        const ovInp = tr.querySelector('.js-override');
        const efectivoEl = tr.querySelector('.js-efectivo');

        const ov = ovInp ? parseGs(ovInp.value) : 0;
        const effective = (ovInp && onlyDigits(ovInp.value).length > 0) ? ov : base;

        if(efectivoEl) efectivoEl.textContent = formatGs(effective) || '0';
    }

    document.addEventListener('input', function(e){
        if(e.target && e.target.classList.contains('js-gs')){
            e.target.value = formatGs(e.target.value);
            const tr = e.target.closest('tr');
            if(tr) recalcRow(tr);
        }
    });

    document.querySelectorAll('#tbl-clinica tbody tr').forEach(tr => recalcRow(tr));

    const sel = document.getElementById('sel-clinica');
    if(sel){
        sel.addEventListener('change', function(){
            if(this.value) window.location.href = this.value;
        });
    }
})();
</script>
@endsection

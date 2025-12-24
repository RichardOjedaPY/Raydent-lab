@extends('layouts.admin')
@section('title','Cobro múltiple')
@section('content_header','Cobro múltiple (varios pedidos en un solo pago)')

@section('content')
@php
  $fmtGs = fn($n) => number_format((int)$n, 0, ',', '.');
@endphp

{{-- Filtros --}}
<div class="card mb-3">
  <div class="card-body">
    <form method="get" action="{{ route('admin.pagos.multiple.create') }}" class="row g-2 align-items-end">

      <div class="col-md-4">
        <label class="small text-muted">Clínica</label>
        <select name="clinica_id" class="form-control">
          <option value="">— Seleccionar —</option>
          @foreach($clinicas as $c)
            <option value="{{ $c->id }}" @selected((int)request('clinica_id')===(int)$c->id)>{{ $c->nombre }}</option>
          @endforeach
        </select>
        <small class="text-muted">El cobro múltiple se registra por clínica (recomendado).</small>
      </div>

      <div class="col-md-2">
        <label class="small text-muted">Desde</label>
        <input type="date" name="desde" value="{{ request('desde') }}" class="form-control">
      </div>

      <div class="col-md-2">
        <label class="small text-muted">Hasta</label>
        <input type="date" name="hasta" value="{{ request('hasta') }}" class="form-control">
      </div>

      <div class="col-md-2">
        <label class="small text-muted">Código pedido</label>
        <input name="codigo" value="{{ request('codigo') }}" class="form-control" placeholder="Ej: P-000123">
      </div>

      <div class="col-md-2 d-flex gap-2">
        <button class="btn btn-primary w-100">Filtrar</button>
        <a href="{{ route('admin.pagos.multiple.create') }}" class="btn btn-outline-secondary w-100">Limpiar</a>
      </div>

    </form>
  </div>
</div>

{{-- Tabla --}}
<div class="card">
  <div class="card-header d-flex align-items-center justify-content-between">
    <div>
      <strong>Liquidaciones pendientes</strong>
      <div class="small text-muted">Saldo > 0</div>
    </div>

    <button type="button" class="btn btn-success btn-sm" id="btnAbrirCobro" disabled
            data-toggle="modal" data-target="#mdlCobroMultiple">
      <i class="fas fa-cash-register mr-1"></i> Cobrar seleccionados
    </button>
  </div>

  <div class="card-body p-0 table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="thead-light">
        <tr>
          <th style="width:50px" class="text-center">
            <input type="checkbox" id="chkAll">
          </th>
          <th>Pedido</th>
          <th>Clínica</th>
          <th>Paciente</th>
          <th class="text-end">Total</th>
          <th class="text-end">Pagado</th>
          <th class="text-end">Saldo</th>
        </tr>
      </thead>
      <tbody>
        @forelse($liquidaciones as $liq)
          @php
            $pedido = $liq->pedido;
            $total  = (int)($liq->total_gs ?? 0);
            $pagado = (int)($liq->pagado_gs ?? 0);
            $saldo  = max(0, $total - $pagado);
          @endphp
          <tr>
            <td class="text-center">
              <input type="checkbox" class="chkLiq"
                     value="{{ $liq->id }}"
                     data-saldo="{{ $saldo }}"
                     data-pedido="{{ $pedido->codigo_pedido ?? ('#'.$liq->pedido_id) }}"
                     data-liq="{{ $liq->id }}">
            </td>

            <td class="fw-semibold">
              {{ $pedido->codigo_pedido ?? ('#'.$liq->pedido_id) }}
              <div class="small text-muted">Liq #{{ $liq->id }}</div>
            </td>

            <td>{{ $pedido->clinica->nombre ?? '—' }}</td>
            <td>{{ $pedido->paciente->nombre ?? '—' }}</td>

            <td class="text-end">{{ $fmtGs($total) }}</td>
            <td class="text-end">{{ $fmtGs($pagado) }}</td>
            <td class="text-end">
              <span class="badge bg-warning text-dark">Gs {{ $fmtGs($saldo) }}</span>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="7" class="text-center text-muted py-4">No hay liquidaciones pendientes con estos filtros.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="card-footer d-flex align-items-center justify-content-between">
    <div class="small text-muted">
      Seleccionados: <strong id="txtCount">0</strong>
      <span class="mx-2">•</span>
      Total saldo: <strong>Gs <span id="txtTotalSaldo">0</span></strong>
    </div>
    <div>
      {{ $liquidaciones->links() }}
    </div>
  </div>
</div>

{{-- Modal Cobro múltiple --}}
<div class="modal fade" id="mdlCobroMultiple" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <form method="POST" action="{{ route('admin.pagos.multiple.store') }}" id="frmCobroMultiple">
      @csrf

      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Confirmar cobro múltiple</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <div class="modal-body">

          {{-- Inputs cabecera --}}
          <div class="row">
            <div class="col-md-3">
              <label>Fecha</label>
              <input type="date" name="fecha" class="form-control" value="{{ now()->toDateString() }}" required>
            </div>

            <div class="col-md-3">
              <label>Método</label>
              <select name="metodo" class="form-control" required>
                <option value="efectivo">Efectivo</option>
                <option value="transferencia">Transferencia</option>
                <option value="tarjeta">Tarjeta</option>
                <option value="otros">Otros</option>
              </select>
            </div>

            <div class="col-md-3">
              <label>Monto total (Gs)</label>
              <input type="text" name="monto_gs" id="inpMontoTotal" class="form-control" required>
              <small class="text-muted">Por defecto = total saldo seleccionado.</small>
            </div>

            <div class="col-md-3">
              <label>Referencia (opcional)</label>
              <input type="text" name="referencia" class="form-control" maxlength="120">
            </div>
          </div>

          <div class="mt-3">
            <label>Observación (opcional)</label>
            <textarea name="observacion" class="form-control" rows="2"></textarea>
          </div>

          <hr>

          {{-- Resumen selección --}}
          <div class="d-flex align-items-center justify-content-between mb-2">
            <div class="small text-muted">
              Se aplicará el monto en orden a las liquidaciones seleccionadas.
              Si sobra dinero, quedará como <strong>pago a cuenta</strong>.
            </div>
            <div class="badge badge-info">
              Total saldo: Gs <span id="mdlTotalSaldo">0</span>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0">
              <thead class="thead-light">
                <tr>
                  <th style="width:90px">Liq</th>
                  <th>Pedido</th>
                  <th class="text-end" style="width:160px">Saldo</th>
                </tr>
              </thead>
              <tbody id="mdlRows">
                {{-- JS llena --}}
              </tbody>
            </table>
          </div>

          {{-- Hidden inputs: liquidaciones[] --}}
          <div id="hidContainer"></div>

        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-success">
            <i class="fas fa-check mr-1"></i> Confirmar cobro
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
  (function () {
  
    function fmtGs(n){
      n = parseInt(n || 0, 10);
      return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }
  
    function getSelected(){
      return Array.from(document.querySelectorAll('.chkLiq:checked')).map(chk => ({
        id: chk.value,
        saldo: parseInt(chk.getAttribute('data-saldo') || '0', 10),
        pedido: chk.getAttribute('data-pedido') || '',
        liq: chk.getAttribute('data-liq') || ''
      }));
    }
  
    function refreshFooter(){
      const sel = getSelected();
      const totalSaldo = sel.reduce((a,x)=>a + (x.saldo||0), 0);
  
      document.getElementById('txtCount').textContent = sel.length;
      document.getElementById('txtTotalSaldo').textContent = fmtGs(totalSaldo);
  
      const btn = document.getElementById('btnAbrirCobro');
      btn.disabled = sel.length === 0;
    }
  
    function buildModal(){
      const sel = getSelected();
      const totalSaldo = sel.reduce((a,x)=>a + (x.saldo||0), 0);
  
      document.getElementById('mdlTotalSaldo').textContent = fmtGs(totalSaldo);
  
      // Si el usuario no tocó el input, seteamos por defecto el total saldo
      const inpMonto = document.getElementById('inpMontoTotal');
      if (!inpMonto.value || inpMonto.value.trim() === '' || inpMonto.dataset.auto === '1') {
        inpMonto.value = fmtGs(totalSaldo);
        inpMonto.dataset.auto = '1';
      }
  
      // Rows
      const tbody = document.getElementById('mdlRows');
      tbody.innerHTML = '';
      sel.forEach(x => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td>#${x.liq}</td>
          <td>${x.pedido}</td>
          <td class="text-end">Gs ${fmtGs(x.saldo)}</td>
        `;
        tbody.appendChild(tr);
      });
  
      // Hidden inputs
      const hid = document.getElementById('hidContainer');
      hid.innerHTML = '';
      sel.forEach(x => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'liquidaciones[]';
        input.value = x.id;
        hid.appendChild(input);
      });
    }
  
    // Check all
    const chkAll = document.getElementById('chkAll');
    if (chkAll){
      chkAll.addEventListener('change', function(){
        document.querySelectorAll('.chkLiq').forEach(x => x.checked = chkAll.checked);
        refreshFooter();
      });
    }
  
    // Individual checks
    document.querySelectorAll('.chkLiq').forEach(chk => {
      chk.addEventListener('change', function(){
        refreshFooter();
      });
    });
  
    // Si el usuario escribe manualmente el monto, ya no lo auto pisamos
    const inpMonto = document.getElementById('inpMontoTotal');
    if (inpMonto){
      inpMonto.addEventListener('input', function(){
        this.dataset.auto = '0';
      });
    }
  
    // Al hacer click en "Cobrar seleccionados", armamos el modal ANTES de abrirlo
    const btnAbrir = document.getElementById('btnAbrirCobro');
    if (btnAbrir){
      btnAbrir.addEventListener('click', function(){
        buildModal();
      });
    }
  
    // Seguridad extra: antes de enviar, si no hay liquidaciones, bloquear
    const frm = document.getElementById('frmCobroMultiple');
    if (frm){
      frm.addEventListener('submit', function(e){
        buildModal(); // asegura hidden inputs
        const count = document.querySelectorAll('#hidContainer input[name="liquidaciones[]"]').length;
        if (count === 0){
          e.preventDefault();
          alert('Seleccioná al menos una liquidación.');
          return false;
        }
      });
    }
  
    refreshFooter();
  
  })();
  </script>
  

@endsection

@extends('layouts.admin')
@section('title','Pedidos liquidados')
@section('content_header','Pedidos liquidados')

@section('content')
@php
  $fmtGs = fn($n) => number_format((int)$n, 0, ',', '.');
@endphp

<div class="card mb-3">
  <div class="card-body">
    <form class="row g-2 align-items-end" method="get" action="{{ route('admin.liquidaciones.pedidos_liquidados') }}">
      <div class="col-md-4">
        <label class="small text-muted">Clínica</label>
        <select name="clinica_id" class="form-control">
          <option value="">— Todas —</option>
          @foreach($clinicas as $c)
            <option value="{{ $c->id }}" @selected(request('clinica_id')==$c->id)>{{ $c->nombre }}</option>
          @endforeach
        </select>
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
        <label class="small text-muted">Saldo</label>
        <select name="saldo" class="form-control">
          <option value="">— Todos —</option>
          <option value="con" @selected(request('saldo')==='con')>Con saldo</option>
          <option value="sin" @selected(request('saldo')==='sin')>Sin saldo</option>
        </select>
      </div>

      <div class="col-md-2">
        <label class="small text-muted">Código pedido</label>
        <input name="codigo" value="{{ request('codigo') }}" class="form-control" placeholder="Ej: P-000123">
      </div>

      <div class="col-md-12 d-flex gap-2 mt-2">
        <button class="btn btn-primary">Filtrar</button>
        <a href="{{ route('admin.liquidaciones.pedidos_liquidados') }}" class="btn btn-outline-secondary">Limpiar</a>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-body p-0 table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="thead-light">
        <tr>
          <th>Pedido</th>
          <th>Clínica</th>
          <th>Paciente</th>
          <th class="text-end">Total</th>
          <th class="text-end">Pagado</th>
          <th class="text-end">Saldo</th>
          <th class="text-end">Acción</th>
        </tr>
      </thead>
      <tbody>
        @forelse($liquidaciones as $liq)
          @php
            $total  = (int) ($liq->total_gs ?? 0);
            $pagado = (int) ($liq->pagado_gs ?? 0);
            $saldo  = max(0, $total - $pagado);
            $pedido = $liq->pedido;

            // ✅ ÚLTIMO PAGO relacionado (si existen aplicaciones)
            // Nota: requiere relación $liq->aplicaciones y en PagoAplicacion ->pago()
            $apUlt   = $liq->aplicaciones?->sortByDesc('id')->first();
            $pagoUlt = $apUlt?->pago; // modelo Pago
          @endphp
          <tr>
            <td class="fw-semibold">
              {{ $pedido->codigo_pedido ?? ('#'.$liq->pedido_id) }}
              <div class="small text-muted">Liq #{{ $liq->id }}</div>
            </td>
            <td>{{ $pedido->clinica->nombre ?? '—' }}</td>
            <td>{{ $pedido->paciente->nombre ?? '—' }}</td>

            <td class="text-end">{{ $fmtGs($total) }}</td>
            <td class="text-end">{{ $fmtGs($pagado) }}</td>
            <td class="text-end">
              @if($saldo > 0)
                <span class="badge bg-warning text-dark">Gs {{ $fmtGs($saldo) }}</span>
              @else
                <span class="badge bg-success">Cancelado</span>
              @endif
            </td>

            <td class="text-end">
              {{-- Ver pedido --}}
              @if(\Route::has('admin.pedidos.show'))
                <a class="btn btn-sm btn-outline-primary"
                   href="{{ route('admin.pedidos.show', $pedido) }}">
                  Ver pedido
                </a>
              @else
                <span class="text-muted small">—</span>
              @endif

              {{-- ✅ Ver pago (último) --}}
              @if($pagoUlt && \Route::has('admin.pagos.show'))
                <a class="btn btn-sm btn-outline-info"
                   href="{{ route('admin.pagos.show', $pagoUlt) }}">
                  Ver pago
                </a>
              @endif

              {{-- Cobrar (si hay saldo) --}}
              @if($saldo > 0)
                <button type="button"
                        class="btn btn-sm btn-success btn-cobrar"
                        data-toggle="modal"
                        data-target="#mdlCobrar"
                        data-liq-id="{{ $liq->id }}"
                        data-pedido="{{ $pedido->codigo_pedido ?? ('#'.$liq->pedido_id) }}"
                        data-saldo="{{ $saldo }}">
                    Cobrar
                </button>
              @endif
              
            </td>
          </tr>
        @empty
          <tr><td colspan="7" class="text-center text-muted py-4">Sin resultados</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="card-footer">
    {{ $liquidaciones->links() }}
  </div>
</div>

{{-- Modal Cobrar (individual / parcial) --}}
<div class="modal fade" id="mdlCobrar" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-md" role="document">
    <form method="POST" id="frmCobrar" action="#">
      @csrf
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Cobrar pedido liquidado</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <div class="modal-body">
          <div class="small text-muted mb-2">
            Pedido: <span id="txtPedido" class="font-weight-semibold"></span>
          </div>

          <div class="alert alert-info py-2 mb-3">
            Saldo: <strong>Gs <span id="txtSaldo"></span></strong>
          </div>

          <div class="form-group">
            <label>Fecha</label>
            <input type="date" name="fecha" class="form-control" value="{{ now()->toDateString() }}" required>
          </div>

          <div class="form-group">
            <label>Método</label>
            <select name="metodo" class="form-control" required>
              <option value="efectivo">Efectivo</option>
              <option value="transferencia">Transferencia</option>
              <option value="tarjeta">Tarjeta</option>
              <option value="otros">Otros</option>
            </select>
          </div>

          <div class="form-group">
            <label>Monto (Gs)</label>
            <input type="text" name="monto_gs" id="inpMontoGs" class="form-control" placeholder="Ej: 50.000" required>
            <small class="text-muted">Podés cobrar parcial. Si el monto supera el saldo, el excedente queda como pago a cuenta.</small>
          </div>

          <div class="form-group">
            <label>Referencia (opcional)</label>
            <input type="text" name="referencia" class="form-control" maxlength="120">
          </div>

          <div class="form-group mb-0">
            <label>Observación (opcional)</label>
            <textarea name="observacion" class="form-control" rows="2"></textarea>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-success">Confirmar cobro</button>
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

    document.querySelectorAll('.btn-cobrar').forEach(function(btn){
      btn.addEventListener('click', function(){
        var liqId  = this.getAttribute('data-liq-id');
        var pedido = this.getAttribute('data-pedido');
        var saldo  = this.getAttribute('data-saldo');

        document.getElementById('txtPedido').textContent = pedido;
        document.getElementById('txtSaldo').textContent  = fmtGs(saldo);

        var action = "{{ url('admin/liquidaciones') }}/" + liqId + "/pago-individual";
        document.getElementById('frmCobrar').setAttribute('action', action);

        document.getElementById('inpMontoGs').value = fmtGs(saldo);
      });
    });
  })();
</script>

@endsection

 {{-- resources/views/admin/activity_logs/show.blade.php --}}

@extends('layouts.admin')

@section('title', 'Detalle Activity Log')
@section('content_header', 'Detalle Activity Log')

@section('content')
@php
  $props   = (array) ($activity->properties ?? []);
  $actor   = optional($activity->causer)->name ?? 'Sistema';
  $event   = strtolower((string) ($activity->event ?? ''));
  $subject = $activity->subject_type ? (class_basename($activity->subject_type) . ' #' . $activity->subject_id) : null;

  $badgeClass = match (true) {
      str_contains($event, 'login')     => 'badge-success',
      str_contains($event, 'logout')    => 'badge-secondary',
      str_contains($event, 'failed')    => 'badge-danger',
      str_contains($event, 'created')   => 'badge-success',
      str_contains($event, 'updated')   => 'badge-info',
      str_contains($event, 'deleted')   => 'badge-danger',
      str_contains($event, 'disabled')  => 'badge-warning',
      str_contains($event, 'pdf')       => 'badge-dark',
      str_contains($event, 'download')  => 'badge-dark',
      str_contains($event, 'denied')    => 'badge-danger',
      str_contains($event, 'missing')   => 'badge-warning',
      default                           => 'badge-light',
  };

  $pretty = function($v){
      if (is_null($v)) return '—';
      if (is_bool($v)) return $v ? 'Sí' : 'No';
      if (is_array($v)) return json_encode($v, JSON_UNESCAPED_UNICODE);
      return (string) $v;
  };

  $changes = data_get($props, 'changes');

  // ✅ Snapshot de pedido (si existe)
  $pedidoSnap = data_get($props, 'pedido');
  if (!is_array($pedidoSnap)) $pedidoSnap = null;

  $sel        = $pedidoSnap ? (array) (data_get($pedidoSnap, 'selecciones') ?? []) : [];
  $checksTrue = $pedidoSnap ? (array) (data_get($pedidoSnap, 'checks_true') ?? []) : [];

  // chips/labels (opcionales)
  $labelPrioridad = function($p){
      $p = strtolower((string)$p);
      return match($p){
          'urgente' => ['label'=>'Urgente', 'class'=>'badge-danger'],
          'normal'  => ['label'=>'Normal',  'class'=>'badge-secondary'],
          default   => ['label'=>($p ?: '—'), 'class'=>'badge-light'],
      };
  };
  $prio = $pedidoSnap ? $labelPrioridad(data_get($pedidoSnap,'prioridad')) : null;

  // resumen corto de “qué pidió”
  $resumenServicios = function(array $sel){
      $parts = [];

      $f = (array) ($sel['fotos'] ?? []);
      $c = (array) ($sel['cefalometrias'] ?? []);
      $p = (array) ($sel['periapical'] ?? []);
      $t = (array) ($sel['tomografia'] ?? []);

      if (count($f)) $parts[] = count($f) . ' foto(s)';
      if (count($c)) $parts[] = count($c) . ' cefalo(s)';
      if (count($p)) $parts[] = 'Periapical: ' . count($p);
      if (count($t)) $parts[] = 'Tomo: ' . count($t);

      return $parts ? implode(' · ', $parts) : '—';
  };
@endphp

<style>
  .card-soft{
    border: 1px solid rgba(148,163,184,.35);
    border-radius: .85rem;
    background: #fff;
    box-shadow: 0 10px 25px rgba(15,23,42,.06);
  }
  .pill{
    border-radius: 999px;
    padding: .25rem .55rem;
    font-weight: 700;
    border: 1px solid rgba(148,163,184,.35);
  }
  .muted{ color:#64748b; }
  .mono{ font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; }
  .kv td{ padding:.45rem .6rem; vertical-align: top; }
  .kv td:first-child{ width: 220px; color:#64748b; }
  .jsonbox{
    background: #0b1220;
    color: #e5e7eb;
    border-radius: .85rem;
    padding: .85rem;
    overflow:auto;
    font-size: 12px;
  }
  .chip{
    display:inline-flex;
    align-items:center;
    gap:.45rem;
    border:1px solid rgba(148,163,184,.35);
    border-radius:999px;
    padding:.35rem .65rem;
    background:#fff;
  }
  .soft-grid{
    display:grid;
    grid-template-columns: repeat(12, 1fr);
    gap: .75rem;
  }
  .soft-col-6{ grid-column: span 12; }
  @media (min-width: 992px){
    .soft-col-6{ grid-column: span 6; }
  }
  .soft-col-12{ grid-column: span 12; }
  .mini-title{
    font-size: .9rem;
    font-weight: 800;
    margin: 0;
  }
  .mini-sub{
    font-size: .78rem;
    color:#64748b;
    margin-top:.15rem;
  }
  .list-tight{ margin:0; padding-left: 1rem; }
  .list-tight li{ margin:.15rem 0; }
</style>

<div class="card card-soft mb-3">
  <div class="card-body">
    <div class="d-flex flex-wrap align-items-start justify-content-between" style="gap:.75rem;">
      <div>
        <div class="d-flex flex-wrap align-items-center" style="gap:.5rem;">
          <span class="mono muted">#{{ $activity->id }}</span>
          <span class="badge badge-light pill">{{ $activity->log_name }}</span>
          <span class="badge {{ $badgeClass }} pill">{{ $activity->event ?? '—' }}</span>
          @if($subject)
            <span class="badge badge-secondary pill">{{ $subject }}</span>
          @endif

          {{-- ✅ Badges “pedido” si existe snapshot --}}
          @if($pedidoSnap)
            @if(data_get($pedidoSnap,'codigo_pedido'))
              <span class="badge badge-light pill">Código: <span class="mono">{{ data_get($pedidoSnap,'codigo_pedido') }}</span></span>
            @endif
            @if($prio)
              <span class="badge {{ $prio['class'] }} pill">{{ $prio['label'] }}</span>
            @endif
          @endif
        </div>

        <div class="mt-2 h5 mb-1">{{ $activity->description }}</div>

        <div class="muted small d-flex flex-wrap" style="gap:.75rem;">
          <span class="chip"><i class="far fa-clock"></i> <span class="mono">{{ optional($activity->created_at)->format('d/m/Y H:i:s') }}</span></span>
          <span class="chip"><i class="far fa-user"></i> {{ $actor }}</span>
          @if(data_get($props,'clinica_id'))
            <span class="chip"><i class="fas fa-hospital"></i> Clínica: <span class="mono">{{ data_get($props,'clinica_id') }}</span></span>
          @endif

          {{-- ✅ Resumen de servicios seleccionados --}}
          @if($pedidoSnap)
            <span class="chip"><i class="fas fa-list-check"></i> <span class="mono">{{ $resumenServicios($sel) }}</span></span>
          @endif
        </div>
      </div>

      <div class="ml-auto">
        <a href="{{ route('admin.activity-logs.index') }}" class="btn btn-default btn-sm">
          <i class="fas fa-arrow-left"></i> Volver
        </a>
      </div>
    </div>
  </div>
</div>

<div class="soft-grid">
  {{-- Panel “mínimo esencial” --}}
  <div class="soft-col-6">
    <div class="card card-soft h-100 mb-3">
      <div class="card-header bg-white">
        <p class="mini-title">Resumen</p>
        <p class="mini-sub">Lo mínimo indispensable para entender qué pasó.</p>
      </div>
      <div class="card-body p-0">
        <table class="table mb-0 kv">
          <tr>
            <td>ID</td>
            <td class="mono">{{ $activity->id }}</td>
          </tr>
          <tr>
            <td>Fecha</td>
            <td class="mono">{{ optional($activity->created_at)->format('d/m/Y H:i:s') }}</td>
          </tr>
          <tr>
            <td>Módulo</td>
            <td><span class="badge badge-light pill">{{ $activity->log_name }}</span></td>
          </tr>
          <tr>
            <td>Evento</td>
            <td><span class="badge {{ $badgeClass }} pill">{{ $activity->event ?? '—' }}</span></td>
          </tr>
          <tr>
            <td>Actor</td>
            <td>{{ $actor }}</td>
          </tr>
          <tr>
            <td>Subject</td>
            <td>{{ $subject ?? '—' }}</td>
          </tr>

          {{-- ✅ Detalle “pedido” si existe snapshot --}}
          @if($pedidoSnap)
            <tr>
              <td>Clínica</td>
              <td>
                <div class="mono">{{ data_get($pedidoSnap,'clinica.nombre') ?? '—' }}</div>
                @if(data_get($pedidoSnap,'clinica.id'))
                  <div class="muted small mono">ID: {{ data_get($pedidoSnap,'clinica.id') }}</div>
                @endif
              </td>
            </tr>

            <tr>
              <td>Paciente</td>
              <td>
                <div class="mono">{{ data_get($pedidoSnap,'paciente.nombre') ?? '—' }}</div>
                @if(data_get($pedidoSnap,'paciente.id'))
                  <div class="muted small mono">ID: {{ data_get($pedidoSnap,'paciente.id') }}</div>
                @endif
                @if(data_get($pedidoSnap,'paciente.documento'))
                  <div class="muted small mono">CI: {{ data_get($pedidoSnap,'paciente.documento') }}</div>
                @endif
              </td>
            </tr>

            <tr>
              <td>Consulta</td>
              <td class="mono">
                @if(data_get($pedidoSnap,'consulta.id'))
                  #{{ data_get($pedidoSnap,'consulta.id') }}
                  @if(data_get($pedidoSnap,'consulta.fecha_hora'))
                    <span class="muted">({{ data_get($pedidoSnap,'consulta.fecha_hora') }})</span>
                  @endif
                  @if(data_get($pedidoSnap,'consulta.motivo'))
                    <div class="muted small">{{ data_get($pedidoSnap,'consulta.motivo') }}</div>
                  @endif
                @else
                  —
                @endif
              </td>
            </tr>

            <tr>
              <td>Prioridad</td>
              <td>
                @if($prio)
                  <span class="badge {{ $prio['class'] }} pill">{{ $prio['label'] }}</span>
                @else
                  —
                @endif
              </td>
            </tr>

            <tr>
              <td>Agenda</td>
              <td class="mono">
                {{ data_get($pedidoSnap,'agenda.fecha') ?? '—' }}
                {{ data_get($pedidoSnap,'agenda.hora') ? (' ' . data_get($pedidoSnap,'agenda.hora')) : '' }}
              </td>
            </tr>

            <tr>
              <td>Doctor</td>
              <td>
                <div class="mono">{{ data_get($pedidoSnap,'doctor.nombre') ?? '—' }}</div>
                @if(data_get($pedidoSnap,'doctor.telefono'))
                  <div class="muted small mono">{{ data_get($pedidoSnap,'doctor.telefono') }}</div>
                @endif
                @if(data_get($pedidoSnap,'doctor.email'))
                  <div class="muted small mono">{{ data_get($pedidoSnap,'doctor.email') }}</div>
                @endif
              </td>
            </tr>

            @if(data_get($pedidoSnap,'documentacion_tipo') || data_get($pedidoSnap,'descripcion_caso'))
              <tr>
                <td>Documentación / Caso</td>
                <td>
                  @if(data_get($pedidoSnap,'documentacion_tipo'))
                    <div class="mono">Tipo: {{ data_get($pedidoSnap,'documentacion_tipo') }}</div>
                  @endif
                  @if(data_get($pedidoSnap,'descripcion_caso'))
                    <div class="muted small" style="white-space: pre-wrap;">{{ data_get($pedidoSnap,'descripcion_caso') }}</div>
                  @endif
                </td>
              </tr>
            @endif

            <tr>
              <td>Selecciones</td>
              <td>
                <div class="small">
                  <div><strong>Fotos:</strong> <span class="mono">{{ implode(', ', (array)($sel['fotos'] ?? [])) ?: '—' }}</span></div>
                  <div><strong>Cefalometrías:</strong> <span class="mono">{{ implode(', ', (array)($sel['cefalometrias'] ?? [])) ?: '—' }}</span></div>
                  <div><strong>Periapical:</strong> <span class="mono">{{ implode(', ', (array)($sel['periapical'] ?? [])) ?: '—' }}</span></div>
                  <div><strong>Tomografía:</strong> <span class="mono">{{ implode(', ', (array)($sel['tomografia'] ?? [])) ?: '—' }}</span></div>
                </div>
              </td>
            </tr>

            <tr>
              <td>Checks (true)</td>
              <td class="mono">
                {{ !empty($checksTrue) ? implode(', ', $checksTrue) : '—' }}
              </td>
            </tr>
          @endif
        </table>
      </div>
    </div>
  </div>

  {{-- Panel técnico mínimo --}}
  <div class="soft-col-6">
    <div class="card card-soft h-100 mb-3">
      <div class="card-header bg-white">
        <p class="mini-title">Contexto técnico</p>
        <p class="mini-sub">Ruta, método, IP y URL (lo mínimo para rastrear).</p>
      </div>
      <div class="card-body p-0">
        <table class="table mb-0 kv">
          <tr>
            <td>Route</td>
            <td class="mono">{{ data_get($props,'route') ?? '—' }}</td>
          </tr>
          <tr>
            <td>Método</td>
            <td class="mono">{{ data_get($props,'method') ?? '—' }}</td>
          </tr>
          <tr>
            <td>IP</td>
            <td class="mono">{{ data_get($props,'ip') ?? '—' }}</td>
          </tr>
          <tr>
            <td>URL</td>
            <td class="mono" style="word-break: break-word;">{{ data_get($props,'url') ?? '—' }}</td>
          </tr>
          <tr>
            <td>User-Agent</td>
            <td class="mono" style="word-break: break-word;">{{ data_get($props,'user_agent') ?? '—' }}</td>
          </tr>

          {{-- ✅ Datos “técnicos” del pedido si existen --}}
          @if($pedidoSnap)
            <tr>
              <td>Código interno</td>
              <td class="mono">{{ data_get($pedidoSnap,'codigo') ?? '—' }}</td>
            </tr>
            <tr>
              <td>Estado actual</td>
              <td class="mono">{{ data_get($pedidoSnap,'estado') ?? '—' }}</td>
            </tr>
            <tr>
              <td>Fecha solicitud</td>
              <td class="mono">{{ data_get($pedidoSnap,'fecha_solicitud') ?? '—' }}</td>
            </tr>
          @endif
        </table>
      </div>
    </div>
  </div>

  {{-- ✅ Panel “Campos cargados” (solo si hay snapshot) --}}
  @if($pedidoSnap)
    <div class="soft-col-12">
      <div class="card card-soft mb-3">
        <div class="card-header bg-white">
          <p class="mini-title">Datos captados del pedido</p>
          <p class="mini-sub">Esto es lo que se guardó en el log al momento de crear/editar.</p>
        </div>

        <div class="card-body">
          <div class="row">
            <div class="col-lg-6 mb-3">
              <div class="muted small mb-2">Datos clínicos</div>
              <ul class="list-tight">
                <li><strong>Dirección:</strong> <span class="mono">{{ data_get($pedidoSnap,'direccion') ?? '—' }}</span></li>
                <li><strong>Paciente documento (pedido):</strong> <span class="mono">{{ data_get($pedidoSnap,'paciente_documento') ?? '—' }}</span></li>
                <li><strong>RX Panorámica trazado región:</strong> <span class="mono">{{ data_get($pedidoSnap,'rx_panoramica_trazado_region') ?? '—' }}</span></li>
                <li><strong>RX Periapical región:</strong> <span class="mono">{{ data_get($pedidoSnap,'rx_periapical_region') ?? '—' }}</span></li>
                <li><strong>CT parcial zona:</strong> <span class="mono">{{ data_get($pedidoSnap,'ct_parcial_zona') ?? '—' }}</span></li>
                <li><strong>Entrega software detalle:</strong> <span class="mono">{{ data_get($pedidoSnap,'entrega_software_detalle') ?? '—' }}</span></li>
              </ul>
            </div>

            <div class="col-lg-6 mb-3">
              <div class="muted small mb-2">Notas / Adjuntos / Flags</div>
              <ul class="list-tight">
                <li><strong>Documentación tipo:</strong> <span class="mono">{{ data_get($pedidoSnap,'documentacion_tipo') ?? '—' }}</span></li>
                <li><strong>Descripción caso:</strong>
                  <div class="muted" style="white-space: pre-wrap;">{{ data_get($pedidoSnap,'descripcion_caso') ?? '—' }}</div>
                </li>
                <li><strong>Checks activos:</strong> <span class="mono">{{ !empty($checksTrue) ? implode(', ', $checksTrue) : '—' }}</span></li>
              </ul>
            </div>
          </div>

          {{-- JSON de snapshot (solo pedido) --}}
          <div class="mt-2">
            <div class="d-flex align-items-center justify-content-between">
              <div class="muted small">Snapshot (pedido) en JSON</div>
              <button type="button" class="btn btn-sm btn-outline-secondary" id="btnCopyPedido">
                <i class="far fa-copy"></i> Copiar snapshot
              </button>
            </div>
            <pre class="jsonbox mb-0 mt-2" id="pedidoJson">{{ json_encode($pedidoSnap, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
          </div>
        </div>
      </div>
    </div>
  @endif
</div>

{{-- Acordeones: cambios + propiedades completas + raw json --}}
<div class="card card-soft">
  <div class="card-header bg-white d-flex align-items-center justify-content-between">
    <div>
      <strong>Detalles</strong>
      <div class="muted small">Cambios (si aplica) y propiedades completas.</div>
    </div>
    <button type="button" class="btn btn-sm btn-outline-primary" id="btnCopyJson">
      <i class="far fa-copy"></i> Copiar JSON
    </button>
  </div>

  <div class="card-body">
    <div id="accordionLogs">

      {{-- Cambios (si existe props['changes']) --}}
      <div class="card mb-2" style="border-radius:.85rem; overflow:hidden; border:1px solid rgba(148,163,184,.35);">
        <div class="card-header bg-white" id="hdChanges">
          <button class="btn btn-link p-0 font-weight-bold" data-toggle="collapse" data-target="#colChanges" aria-expanded="true" aria-controls="colChanges">
            Cambios
          </button>
          <div class="muted small">Se muestra solo si tu Audit guardó un “changes”.</div>
        </div>

        <div id="colChanges" class="collapse show" aria-labelledby="hdChanges" data-parent="#accordionLogs">
          <div class="card-body">
            @if(is_array($changes) && count($changes))
              <div class="table-responsive">
                <table class="table table-sm mb-0">
                  <thead>
                    <tr>
                      <th>Campo</th>
                      <th>Antes</th>
                      <th>Después</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($changes as $k => $v)
                      <tr>
                        <td class="mono">{{ $k }}</td>
                        <td style="max-width:420px; word-break:break-word;">{{ $pretty(data_get($v,'before')) }}</td>
                        <td style="max-width:420px; word-break:break-word;">{{ $pretty(data_get($v,'after')) }}</td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            @else
              <div class="muted">— Sin cambios registrados.</div>
            @endif
          </div>
        </div>
      </div>

      {{-- Propiedades (key/value) --}}
      <div class="card mb-2" style="border-radius:.85rem; overflow:hidden; border:1px solid rgba(148,163,184,.35);">
        <div class="card-header bg-white" id="hdProps">
          <button class="btn btn-link p-0 font-weight-bold" data-toggle="collapse" data-target="#colProps" aria-expanded="false" aria-controls="colProps">
            Propiedades (key/value)
          </button>
          <div class="muted small">Vista limpia para lectura rápida.</div>
        </div>

        <div id="colProps" class="collapse" aria-labelledby="hdProps" data-parent="#accordionLogs">
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-sm mb-0 kv">
                @foreach($props as $k => $v)
                  <tr>
                    <td class="mono">{{ $k }}</td>
                    <td style="word-break:break-word;">
                      @if(is_array($v))
                        <span class="mono">{{ json_encode($v, JSON_UNESCAPED_UNICODE) }}</span>
                      @else
                        {{ $pretty($v) }}
                      @endif
                    </td>
                  </tr>
                @endforeach
              </table>
            </div>
          </div>
        </div>
      </div>

      {{-- JSON crudo --}}
      <div class="card" style="border-radius:.85rem; overflow:hidden; border:1px solid rgba(148,163,184,.35);">
        <div class="card-header bg-white" id="hdJson">
          <button class="btn btn-link p-0 font-weight-bold" data-toggle="collapse" data-target="#colJson" aria-expanded="false" aria-controls="colJson">
            JSON crudo
          </button>
          <div class="muted small">Para depuración y soporte.</div>
        </div>

        <div id="colJson" class="collapse" aria-labelledby="hdJson" data-parent="#accordionLogs">
          <div class="card-body">
            <pre class="jsonbox mb-0" id="rawJson">{{ json_encode($activity->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<script>
  (function(){
    function toast(btn, okText){
      btn.classList.remove('btn-outline-primary','btn-outline-secondary');
      btn.classList.add('btn-success');
      var old = btn.innerHTML;
      btn.innerHTML = '<i class="fas fa-check"></i> ' + okText;
      setTimeout(function(){
        btn.classList.remove('btn-success');
        // restaurar “outline” según botón
        if(btn.id === 'btnCopyJson'){
          btn.classList.add('btn-outline-primary');
          btn.innerHTML = '<i class="far fa-copy"></i> Copiar JSON';
        }else{
          btn.classList.add('btn-outline-secondary');
          btn.innerHTML = old;
        }
      }, 1200);
    }

    var btn = document.getElementById('btnCopyJson');
    var pre = document.getElementById('rawJson');
    if(btn && pre){
      btn.addEventListener('click', async function(){
        try{
          await navigator.clipboard.writeText(pre.innerText || '');
          toast(btn,'Copiado');
        }catch(e){}
      });
    }

    var btn2 = document.getElementById('btnCopyPedido');
    var pre2 = document.getElementById('pedidoJson');
    if(btn2 && pre2){
      btn2.addEventListener('click', async function(){
        try{
          await navigator.clipboard.writeText(pre2.innerText || '');
          toast(btn2,'Copiado');
        }catch(e){}
      });
    }
  })();
</script>
@endsection

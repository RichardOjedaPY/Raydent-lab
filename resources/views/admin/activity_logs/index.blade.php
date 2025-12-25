@extends('layouts.admin')

@section('title', 'Activity Logs')
@section('content_header', 'Activity Logs')

@section('content')
@php
  $event = fn($v) => strtolower((string)($v ?? ''));
  $badgeClass = function ($ev) use ($event) {
      $ev = $event($ev);
      return match (true) {
          str_contains($ev, 'login')     => 'badge-success',
          str_contains($ev, 'logout')    => 'badge-secondary',
          str_contains($ev, 'failed')    => 'badge-danger',
          str_contains($ev, 'created')   => 'badge-success',
          str_contains($ev, 'updated')   => 'badge-info',
          str_contains($ev, 'deleted')   => 'badge-danger',
          str_contains($ev, 'disabled')  => 'badge-warning',
          str_contains($ev, 'pdf')       => 'badge-dark',
          str_contains($ev, 'download')  => 'badge-dark',
          str_contains($ev, 'denied')    => 'badge-danger',
          str_contains($ev, 'missing')   => 'badge-warning',
          default                        => 'badge-light',
      };
  };

  $subjectLabel = function($a) {
      if (!$a->subject_type) return null;
      return class_basename($a->subject_type) . ' #' . $a->subject_id;
  };

  $actorLabel = fn($a) => optional($a->causer)->name ?? 'Sistema';
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
  .line-clamp-2{
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow:hidden;
  }
  .kpi{
    border: 1px solid rgba(148,163,184,.35);
    border-radius: .85rem;
    padding: .85rem;
    background: linear-gradient(180deg, rgba(248,250,252,1) 0%, rgba(255,255,255,1) 100%);
  }
  .kpi .v{ font-weight:800; font-size:1.1rem; }
  .kpi .t{ font-size:.8rem; color:#64748b; text-transform:uppercase; letter-spacing:.04em; }
  .filters .form-control{ border-radius:.75rem; }
  .filters .btn{ border-radius:.75rem; }
  .log-row{
    border: 1px solid rgba(148,163,184,.25);
    border-radius: .85rem;
    padding: .85rem;
    background:#fff;
    transition: transform .08s ease, box-shadow .08s ease;
  }
  .log-row:hover{
    transform: translateY(-1px);
    box-shadow: 0 12px 25px rgba(15,23,42,.08);
  }
  .mono{ font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; }
</style>

<div class="card card-soft mb-3">
  <div class="card-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between" style="gap: .75rem;">
      <div>
        <div class="h5 mb-0">Auditoría</div>
        <div class="muted small">Registro de acciones (módulo / evento / actor / subject).</div>
      </div>

      <div class="d-flex flex-wrap" style="gap:.5rem;">
        <div class="kpi">
          <div class="t">Resultados</div>
          <div class="v">{{ number_format((int)$totalFiltered, 0, ',', '.') }}</div>
        </div>
        <div class="kpi">
          <div class="t">Hoy</div>
          <div class="v">{{ number_format((int)$todayFiltered, 0, ',', '.') }}</div>
        </div>
        <div class="kpi">
          <div class="t">Últimos 7 días</div>
          <div class="v">{{ number_format((int)$last7Filtered, 0, ',', '.') }}</div>
        </div>
      </div>
    </div>

    <hr>

    {{-- Filtros compactos --}}
    <form method="GET" class="filters mb-0">
      <div class="row">
        <div class="col-lg-4 mb-2">
          <label class="small muted mb-1">Buscar</label>
          <input type="text" name="q" value="{{ request('q') }}" class="form-control"
                 placeholder="Descripción, módulo, subject_type, causer_type...">
        </div>

        <div class="col-lg-3 mb-2">
          <label class="small muted mb-1">Módulo</label>
          <select name="log_name" class="form-control">
            <option value="">Todos</option>
            @foreach($logNames as $ln)
              <option value="{{ $ln }}" @selected(request('log_name') === $ln)>{{ $ln }}</option>
            @endforeach
          </select>
        </div>

        <div class="col-lg-2 mb-2">
          <label class="small muted mb-1">Evento</label>
          <select name="event" class="form-control">
            <option value="">Todos</option>
            @foreach($events as $ev)
              <option value="{{ $ev }}" @selected(request('event') === $ev)>{{ $ev }}</option>
            @endforeach
          </select>
        </div>

        <div class="col-lg-3 mb-2">
          <label class="small muted mb-1">Rango</label>
          <div class="d-flex" style="gap:.5rem;">
            <input type="date" name="from" value="{{ request('from') }}" class="form-control">
            <input type="date" name="to" value="{{ request('to') }}" class="form-control">
          </div>
        </div>

        <div class="col-12 mt-2 d-flex flex-wrap" style="gap:.5rem;">
          <button class="btn btn-primary btn-sm">
            <i class="fas fa-filter"></i> Aplicar
          </button>
          <a href="{{ route('admin.activity-logs.index') }}" class="btn btn-default btn-sm">
            Limpiar
          </a>

          <div class="ml-auto muted small d-flex align-items-center" style="gap:.5rem;">
            <span class="pill mono">page {{ $logs->currentPage() }}</span>
            <span class="pill mono">{{ $logs->perPage() }}/p</span>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>

{{-- Listado tipo feed (moderno y responsive) --}}
<div class="d-flex flex-column" style="gap:.75rem;">
  @forelse($logs as $a)
    @php
      $sub = $subjectLabel($a);
      $props = (array) ($a->properties ?? []);
      $route = data_get($props, 'route');
      $ip    = data_get($props, 'ip');
    @endphp

    <div class="log-row">
      <div class="d-flex flex-wrap align-items-start justify-content-between" style="gap:.75rem;">
        <div class="d-flex align-items-start" style="gap:.75rem; min-width: 260px;">
          <div class="text-center" style="width: 64px;">
            <div class="mono small muted">#{{ $a->id }}</div>
            <div class="mono font-weight-bold">{{ optional($a->created_at)->format('d/m') }}</div>
            <div class="mono small muted">{{ optional($a->created_at)->format('H:i') }}</div>
          </div>

          <div>
            <div class="d-flex flex-wrap align-items-center" style="gap:.5rem;">
              <span class="badge badge-light pill">{{ $a->log_name }}</span>
              <span class="badge {{ $badgeClass($a->event) }} pill">{{ $a->event ?? '—' }}</span>
              @if($sub)
                <span class="badge badge-secondary pill">{{ $sub }}</span>
              @endif
            </div>

            <div class="mt-2 line-clamp-2">
              <div class="font-weight-bold">{{ $a->description }}</div>
            </div>

            <div class="mt-2 small muted d-flex flex-wrap" style="gap:.75rem;">
              <span><i class="far fa-user"></i> {{ $actorLabel($a) }}</span>
              @if($route)<span class="mono"><i class="fas fa-route"></i> {{ $route }}</span>@endif
              @if($ip)<span class="mono"><i class="fas fa-network-wired"></i> {{ $ip }}</span>@endif
            </div>
          </div>
        </div>

        <div class="ml-auto">
          <a href="{{ route('admin.activity-logs.show', $a) }}" class="btn btn-sm btn-outline-primary">
            Ver detalle <i class="fas fa-chevron-right ml-1"></i>
          </a>
        </div>
      </div>
    </div>
  @empty
    <div class="card card-soft">
      <div class="card-body text-center muted p-5">
        Sin registros con los filtros actuales.
      </div>
    </div>
  @endforelse
</div>

<div class="mt-3">
  {{ $logs->links() }}
</div>
@endsection

@extends('layouts.admin')

@section('title', 'Activity Logs')
@section('content_header', 'Activity Logs')

@section('content')
    <div class="card">
        <div class="card-header">
            <form method="GET" class="mb-0">
                <div class="row">
                    <div class="col-md-4 mb-2">
                        <input type="text" name="q" value="{{ request('q') }}"
                               class="form-control" placeholder="Buscar (descripción, módulo, tipo...)">
                    </div>

                    <div class="col-md-3 mb-2">
                        <select name="log_name" class="form-control">
                            <option value="">-- Módulo (log_name) --</option>
                            @foreach($logNames as $ln)
                                <option value="{{ $ln }}" @selected(request('log_name') === $ln)>{{ $ln }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2 mb-2">
                        <select name="event" class="form-control">
                            <option value="">-- Evento --</option>
                            @foreach($events as $ev)
                                <option value="{{ $ev }}" @selected(request('event') === $ev)>{{ $ev }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3 mb-2">
                        <div class="d-flex" style="gap:.5rem;">
                            <input type="date" name="from" value="{{ request('from') }}" class="form-control">
                            <input type="date" name="to" value="{{ request('to') }}" class="form-control">
                        </div>
                    </div>

                    <div class="col-12 mt-2">
                        <button class="btn btn-primary btn-sm">
                            <i class="fas fa-filter"></i> Filtrar
                        </button>
                        <a href="{{ route('admin.activity-logs.index') }}" class="btn btn-default btn-sm">
                            Limpiar
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <div class="card-body p-0 table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Fecha</th>
                        <th>Módulo</th>
                        <th>Evento</th>
                        <th>Descripción</th>
                        <th>Actor</th>
                        <th>Subject</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $a)
                        @php
                            $badge = match($a->event) {
                                'created' => 'badge-success',
                                'updated' => 'badge-info',
                                'deleted' => 'badge-danger',
                                default   => 'badge-secondary'
                            };
                        @endphp
                        <tr>
                            <td class="text-muted">{{ $a->id }}</td>
                            <td class="text-nowrap">{{ optional($a->created_at)->format('d/m/Y H:i') }}</td>
                            <td class="text-nowrap"><span class="badge badge-light">{{ $a->log_name }}</span></td>
                            <td class="text-nowrap"><span class="badge {{ $badge }}">{{ $a->event ?? '-' }}</span></td>
                            <td style="min-width: 260px;">{{ $a->description }}</td>

                            <td class="text-nowrap">
                                {{ optional($a->causer)->name ?? 'Sistema' }}
                            </td>

                            <td class="text-nowrap">
                                @if($a->subject_type)
                                    {{ class_basename($a->subject_type) }} #{{ $a->subject_id }}
                                @else
                                    -
                                @endif
                            </td>

                            <td class="text-right">
                                <a href="{{ route('admin.activity-logs.show', $a) }}" class="btn btn-sm btn-outline-primary">
                                    Ver
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted p-4">Sin registros.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer">
            {{ $logs->links() }}
        </div>
    </div>
@endsection

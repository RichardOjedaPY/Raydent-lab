    @extends('layouts.admin')

    @section('title', 'Detalle Activity Log')
    @section('content_header', 'Detalle Activity Log')

    @section('content')
        <div class="card">
            <div class="card-header">
                <a href="{{ route('admin.activity-logs.index') }}" class="btn btn-default btn-sm">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>

            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-md-3">ID</dt>
                    <dd class="col-md-9">{{ $activity->id }}</dd>

                    <dt class="col-md-3">Fecha</dt>
                    <dd class="col-md-9">{{ optional($activity->created_at)->format('d/m/Y H:i:s') }}</dd>

                    <dt class="col-md-3">Módulo (log_name)</dt>
                    <dd class="col-md-9">{{ $activity->log_name }}</dd>

                    <dt class="col-md-3">Evento</dt>
                    <dd class="col-md-9">{{ $activity->event ?? '-' }}</dd>

                    <dt class="col-md-3">Descripción</dt>
                    <dd class="col-md-9">{{ $activity->description }}</dd>

                    <dt class="col-md-3">Actor</dt>
                    <dd class="col-md-9">{{ optional($activity->causer)->name ?? 'Sistema' }}</dd>

                    <dt class="col-md-3">Subject</dt>
                    <dd class="col-md-9">
                        @if($activity->subject_type)
                            {{ class_basename($activity->subject_type) }} #{{ $activity->subject_id }}
                        @else
                            -
                        @endif
                    </dd>

                    <dt class="col-md-3">Propiedades</dt>
                    <dd class="col-md-9">
                        <pre class="mb-0" style="white-space: pre-wrap;">{{ json_encode($activity->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </dd>
                </dl>
            </div>
        </div>
    @endsection

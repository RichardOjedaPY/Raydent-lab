@extends('layouts.admin')

@section('title', 'Consultas')
@section('content_header', 'Consultas')

@section('content')

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <form method="GET" action="{{ route('admin.consultas.index') }}" class="form-inline">
                <div class="input-group input-group-sm mr-2">
                    <input type="text" name="search" class="form-control"
                           placeholder="Buscar por paciente o documento"
                           value="{{ $search }}">
                    <span class="input-group-append">
                        <button class="btn btn-outline-secondary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </span>
                </div>

                <select name="clinica_id" class="form-control form-control-sm" onchange="this.form.submit()">
                    <option value="">Todas las clínicas</option>
                    @foreach($clinicas as $c)
                        <option value="{{ $c->id }}"
                            {{ (string)$clinicaId === (string)$c->id ? 'selected' : '' }}>
                            {{ $c->nombre }}
                        </option>
                    @endforeach
                </select>
            </form>

            @can('consultas.create')
                <a href="{{ route('admin.consultas.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus mr-1"></i> Nueva consulta
                </a>
            @endcan
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Fecha / hora</th>
                            <th>Paciente</th>
                            <th>Clínica</th>
                            <th>Motivo</th>
                            <th>Profesional</th>
                            <th style="width: 150px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($consultas as $c)
                            <tr>
                                <td>{{ $c->fecha_hora?->format('d/m/Y H:i') }}</td>
                                <td>{{ $c->paciente?->nombre }} {{ $c->paciente?->apellido }}</td>
                                <td>{{ $c->clinica?->nombre }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($c->motivo_consulta, 40) }}</td>
                                <td>{{ $c->profesional?->name ?? '—' }}</td>
                                <td>
                                    @can('consultas.view')
                                        <a href="{{ route('admin.consultas.show', $c) }}"
                                           class="btn btn-xs btn-outline-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    @endcan
                                    @can('consultas.update')
                                        <a href="{{ route('admin.consultas.edit', $c) }}"
                                           class="btn btn-xs btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    @endcan
                                    @can('consultas.delete')
                                        <form action="{{ route('admin.consultas.destroy', $c) }}"
                                              method="POST" class="d-inline"
                                              onsubmit="return confirm('¿Eliminar esta consulta?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="btn btn-xs btn-outline-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted p-3">
                                    No hay consultas registradas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($consultas->hasPages())
            <div class="card-footer">
                {{ $consultas->links() }}
            </div>
        @endif
    </div>
@endsection

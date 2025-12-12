@extends('layouts.admin')

@section('title', 'Pacientes')
@section('content_header', 'Pacientes')

@section('content')

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <form method="GET" action="{{ route('admin.pacientes.index') }}" class="form-inline">
                <div class="input-group input-group-sm mr-2">
                    <input type="text" name="search" class="form-control"
                           placeholder="Buscar por nombre, apellido o documento"
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

            @can('pacientes.create')
                <a href="{{ route('admin.pacientes.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus mr-1"></i> Nuevo paciente
                </a>
            @endcan
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th style="width: 60px;">ID</th>
                            <th>Paciente</th>
                            <th>Documento</th>
                            <th>Clínica</th>
                            <th>Teléfono</th>
                            <th style="width: 100px;">Estado</th>
                            <th style="width: 180px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pacientes as $p)
                            <tr>
                                <td>{{ $p->id }}</td>
                                <td>{{ $p->nombre }} {{ $p->apellido }}</td>
                                <td>{{ $p->documento }}</td>
                                <td>{{ $p->clinica?->nombre }}</td>
                                <td>{{ $p->telefono }}</td>
                                <td>
                                    @if($p->is_active)
                                        <span class="badge badge-success">Activo</span>
                                    @else
                                        <span class="badge badge-secondary">Inactivo</span>
                                    @endif
                                </td>
                                <td>
                                    @can('pacientes.view')
                                        <a href="{{ route('admin.pacientes.show', $p) }}"
                                           class="btn btn-xs btn-outline-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    @endcan

                                    @can('pacientes.update')
                                        <a href="{{ route('admin.pacientes.edit', $p) }}"
                                           class="btn btn-xs btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    @endcan

                                    @can('pacientes.delete')
                                        <form action="{{ route('admin.pacientes.toggle-status', $p) }}"
                                              method="POST" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                    class="btn btn-xs {{ $p->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                                    title="Cambiar estado">
                                                @if($p->is_active)
                                                    <i class="fas fa-ban"></i>
                                                @else
                                                    <i class="fas fa-check-circle"></i>
                                                @endif
                                            </button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted p-3">
                                    No hay pacientes para mostrar.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($pacientes->hasPages())
            <div class="card-footer">
                {{ $pacientes->links() }}
            </div>
        @endif
    </div>
@endsection

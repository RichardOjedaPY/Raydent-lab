@extends('layouts.admin')

@section('title', 'Clínicas')
@section('content_header', 'Clínicas')

@section('content')

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <form method="GET" action="{{ route('admin.clinicas.index') }}" class="form-inline">
                <div class="input-group input-group-sm">
                    <input type="text" name="search" class="form-control"
                           placeholder="Buscar nombre, RUC o ciudad"
                           value="{{ $search }}">
                    <span class="input-group-append">
                        <button class="btn btn-outline-secondary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </span>
                </div>
            </form>

            <a href="{{ route('admin.clinicas.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus mr-1"></i> Nueva clínica
            </a>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th style="width: 60px;">ID</th>
                            <th>Nombre</th>
                            <th>RUC</th>
                            <th>Ciudad</th>
                            <th>Plan</th>
                            <th style="width: 110px;">Estado</th>
                            <th style="width: 180px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($clinicas as $c)
                            <tr>
                                <td>{{ $c->id }}</td>
                                <td>{{ $c->nombre }}</td>
                                <td>{{ $c->ruc }}</td>
                                <td>{{ $c->ciudad }}</td>
                                <td>{{ ucfirst($c->plan) }}</td>
                                <td>
                                    @if($c->is_active)
                                        <span class="badge badge-success">Activa</span>
                                    @else
                                        <span class="badge badge-secondary">Inactiva</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.clinicas.edit', $c) }}"
                                       class="btn btn-xs btn-outline-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    <form action="{{ route('admin.clinicas.toggle-status', $c) }}"
                                          method="POST" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                                class="btn btn-xs {{ $c->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                                title="Cambiar estado">
                                            @if($c->is_active)
                                                <i class="fas fa-ban"></i>
                                            @else
                                                <i class="fas fa-check-circle"></i>
                                            @endif
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted p-3">
                                    No hay clínicas para mostrar.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($clinicas->hasPages())
            <div class="card-footer">
                {{ $clinicas->links() }}
            </div>
        @endif
    </div>
@endsection

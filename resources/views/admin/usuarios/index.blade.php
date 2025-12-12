@extends('layouts.admin')

@section('title', 'Usuarios')
@section('content_header', 'Usuarios')

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
            <div>
                <form method="GET" action="{{ route('admin.usuarios.index') }}" class="form-inline">
                    <div class="input-group input-group-sm">
                        <input type="text" name="search" class="form-control"
                               placeholder="Buscar nombre o email"
                               value="{{ $search }}">
                        <span class="input-group-append">
                            <button class="btn btn-outline-secondary" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </span>
                    </div>
                </form>
            </div>

            <a href="{{ route('admin.usuarios.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-user-plus mr-1"></i> Nuevo usuario
            </a>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th style="width: 60px;">ID</th>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Rol</th>
                            <th>Clínica</th> 
                            <th>Tipo usuario</th>
                            <th style="width: 120px;">Estado</th>
                            <th style="width: 160px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $u)
                            <tr>
                                <td>{{ $u->id }}</td>
                                <td>{{ $u->name }}</td>
                                <td>{{ $u->email }}</td>
                                <td>
                                    {{ $u->getRoleNames()->implode(', ') ?: '—' }}
                                </td>
                                <td>{{ $u->clinica?->nombre ?? '—' }}</td>
                                <td>
                                    @php
                                        $esClinica = $u->getRoleNames()->contains('clinica');
                                    @endphp
                                
                                    @if(! $esClinica)
                                        —
                                    @else
                                        @if($u->tipo_usuario_clinica === 'owner')
                                            Owner / Admin clínica
                                        @elseif($u->tipo_usuario_clinica === 'staff')
                                            Staff / Operador
                                        @else
                                            —
                                        @endif
                                    @endif
                                </td>
                                <td>
                                    @if($u->is_active)
                                        <span class="badge badge-success">Activo</span>
                                    @else
                                        <span class="badge badge-secondary">Inactivo</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.usuarios.edit', $u) }}"
                                       class="btn btn-xs btn-outline-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    {{-- Toggle activo / inactivo --}}
                                    <form action="{{ route('admin.usuarios.toggle-status', $u) }}"
                                          method="POST" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                                class="btn btn-xs {{ $u->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                                title="Cambiar estado">
                                            @if($u->is_active)
                                                <i class="fas fa-user-slash"></i>
                                            @else
                                                <i class="fas fa-user-check"></i>
                                            @endif
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted p-3">
                                    No hay usuarios para mostrar.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($users->hasPages())
            <div class="card-footer">
                {{ $users->links() }}
            </div>
        @endif
    </div>
@endsection

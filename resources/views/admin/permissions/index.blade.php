@extends('layouts.admin')

@section('title', 'Permisos')
@section('content_header', 'Permisos')

@section('content')

    <div class="card">
        <div class="card-header">
            <h3 class="card-title text-sm">
                Mapa de permisos por rol
            </h3>
        </div>

        <div class="card-body p-0 table-responsive">
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title text-sm mb-0">Configurar permisos por rol</h3>
                </div>
                <div class="card-body">
                    @foreach($roles as $r)
                        @php
                            $label = match ($r->name) {
                                'clinica' => 'Clínica',
                                'tecnico' => 'Técnico',
                                default   => ucfirst($r->name),
                            };
                        @endphp
                        <a href="{{ route('admin.permissions.edit-role', $r) }}"
                           class="btn btn-sm btn-outline-primary mr-1 mb-1">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            </div>
            
            <table class="table table-striped mb-0 table-sm">
                <thead>
                    <tr>
                        <th>Permiso</th>
                        <th>Roles que lo tienen</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($permissions as $perm)
                        <tr>
                            <td>
                                <code>{{ $perm->name }}</code>
                            </td>
                            <td>
                                @if($perm->roles->isEmpty())
                                    <span class="badge badge-secondary">Sin roles</span>
                                @else
                                    @foreach($perm->roles as $role)
                                        @php
                                            $label = match ($role->name) {
                                                'clinica' => 'Clínica',
                                                'tecnico' => 'Técnico',
                                                default   => ucfirst($role->name),
                                            };
                                        @endphp
                                        <span class="badge badge-info mr-1">
                                            {{ $label }}
                                        </span>
                                    @endforeach
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="text-center text-muted p-3">
                                No hay permisos registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

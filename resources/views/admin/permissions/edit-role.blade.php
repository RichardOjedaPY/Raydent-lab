@extends('layouts.admin')

@section('title', 'Permisos para rol')
@section('content_header', 'Permisos para rol')

@section('content')

    <div class="mb-3">
        <a href="{{ route('admin.permissions.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left mr-1"></i> Volver a listado de permisos
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title text-sm">
                Configurar permisos para el rol:
                <strong>
                    @php
                        $label = match ($role->name) {
                            'clinica' => 'Clínica',
                            'tecnico' => 'Técnico',
                            default   => ucfirst($role->name),
                        };
                    @endphp
                    {{ $label }}
                </strong>
            </h3>
        </div>

        <form action="{{ route('admin.permissions.update-role', $role) }}" method="POST">
            @csrf

            <div class="card-body p-0 table-responsive">
                <table class="table table-striped mb-0 table-sm">
                    <thead>
                        <tr>
                            <th>Módulo</th>
                            @foreach($actions as $actionKey => $actionLabel)
                                <th class="text-center">{{ $actionLabel }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($modules as $moduleKey => $moduleLabel)
                            <tr>
                                <td class="align-middle">
                                    <strong>{{ $moduleLabel }}</strong>
                                    <br>
                                    <small class="text-muted">{{ $moduleKey }}</small>
                                </td>

                                @foreach($actions as $actionKey => $actionLabel)
                                    @php
                                        $permName = "{$moduleKey}.{$actionKey}";
                                        $checked  = $role->hasPermissionTo($permName);
                                    @endphp
                                    <td class="text-center align-middle">
                                        <input type="checkbox"
                                               name="perms[]"
                                               value="{{ $permName }}"
                                               {{ $checked ? 'checked' : '' }}>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save mr-1"></i> Guardar permisos
                </button>
            </div>
        </form>
    </div>
@endsection

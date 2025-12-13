@extends('layouts.admin')

@section('title', 'Permisos para rol')
@section('content_header', 'Permisos para rol')

@section('content')
@php
    $label = match ($role->name) {
        'clinica' => 'Clínica',
        'tecnico' => 'Técnico',
        default   => ucfirst($role->name),
    };

    // helpers de UI (Bootstrap/AdminLTE)
    $chipClass = fn(string $k) => match($k) {
        'view'   => 'badge badge-info',
        'create' => 'badge badge-success',
        'update' => 'badge badge-warning',
        'delete' => 'badge badge-danger',
        default  => 'badge badge-secondary',
    };
@endphp

<style>
    /* UI más prolija sin romper AdminLTE */
    .perm-toolbar .form-control { height: calc(1.8125rem + 2px); }
    .perm-table thead th { position: sticky; top: 0; z-index: 2; background: var(--light, #f8f9fa); }
    .perm-module { min-width: 260px; }
    .perm-actions th { min-width: 110px; }
    .perm-row:hover { background: rgba(0,0,0,.025); }
    .perm-muted-dash { opacity: .55; }
    .perm-switch { transform: translateY(1px); }
    .perm-badge { font-size: .72rem; padding: .35rem .45rem; border-radius: 999px; }
</style>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <div class="d-flex align-items-center">
        <a href="{{ route('admin.permissions.index') }}" class="btn btn-outline-secondary btn-sm mr-2">
            <i class="fas fa-arrow-left mr-1"></i> Volver
        </a>

        <div>
            <div class="h5 mb-0">Permisos del rol</div>
            <div class="text-muted small">
                Configurar accesos para: <strong>{{ $label }}</strong>
            </div>
        </div>
    </div>

    <div class="mt-2 mt-md-0 text-muted small">
        Guard: <strong>{{ $role->guard_name }}</strong> · Rol ID: <strong>{{ $role->id }}</strong>
    </div>
</div>

<div class="card">
    <div class="card-header perm-toolbar">
        <div class="d-flex flex-wrap align-items-center justify-content-between">
            <div class="d-flex flex-wrap align-items-center">
                <div class="mr-2 mb-2 mb-md-0">
                    <span class="badge badge-light p-2">
                        <i class="fas fa-user-shield mr-1"></i> {{ $label }}
                    </span>
                </div>

                <div class="input-group input-group-sm mr-2 mb-2 mb-md-0" style="width: 320px;">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                    </div>
                    <input type="text" id="permSearch" class="form-control"
                           placeholder="Buscar módulo (ej: pedidos, tecnico, resultados)">
                </div>

                <div class="btn-group btn-group-sm mr-2 mb-2 mb-md-0">
                    <button type="button" class="btn btn-outline-primary" id="btnSelectAll">
                        <i class="far fa-check-square mr-1"></i> Marcar visibles
                    </button>
                    <button type="button" class="btn btn-outline-secondary" id="btnUnselectAll">
                        <i class="far fa-square mr-1"></i> Desmarcar visibles
                    </button>
                </div>
            </div>

            <div class="text-muted small mt-2 mt-md-0">
                Nota: los guiones (—) indican acciones que no aplican a ese módulo.
            </div>
        </div>
    </div>

    <form action="{{ route('admin.permissions.update-role', $role) }}" method="POST">
        @csrf

        <div class="card-body p-0 table-responsive">
            <table class="table table-hover table-sm mb-0 perm-table">
                <thead class="perm-actions">
                    <tr>
                        <th class="perm-module">Módulo</th>

                        @foreach($actions as $actionKey => $actionLabel)
                            <th class="text-center">
                                <span class="{{ $chipClass($actionKey) }} perm-badge">
                                    {{ $actionLabel }}
                                </span>
                            </th>
                        @endforeach
                    </tr>
                </thead>

                <tbody id="permTbody">
                    @foreach($modules as $moduleKey => $moduleLabel)
                        <tr class="perm-row" data-module="{{ strtolower($moduleLabel.' '.$moduleKey) }}">
                            <td class="align-middle">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <div class="font-weight-semibold">{{ $moduleLabel }}</div>
                                        <div class="text-muted small">{{ $moduleKey }}</div>
                                    </div>

                                    {{-- Botón por fila: marcar/desmarcar solo lo existente --}}
                                    <div class="btn-group btn-group-sm">
                                        <button type="button"
                                                class="btn btn-light btnRowAll"
                                                data-row="{{ $moduleKey }}"
                                                title="Marcar fila">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button type="button"
                                                class="btn btn-light btnRowNone"
                                                data-row="{{ $moduleKey }}"
                                                title="Desmarcar fila">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </td>

                            @foreach($actions as $actionKey => $actionLabel)
                                @php
                                    $permName   = "{$moduleKey}.{$actionKey}";
                                    $permExists = isset($allPermissions[$permName]); // <- evita excepción
                                    $checked    = $permExists ? $role->hasPermissionTo($permName) : false;
                                @endphp

                                <td class="text-center align-middle">
                                    @if($permExists)
                                        <div class="custom-control custom-switch d-inline-block perm-switch">
                                            <input type="checkbox"
                                                   class="custom-control-input perm-check"
                                                   id="p_{{ md5($permName) }}"
                                                   name="perms[]"
                                                   value="{{ $permName }}"
                                                   {{ $checked ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="p_{{ md5($permName) }}"></label>
                                        </div>
                                    @else
                                        <span class="perm-muted-dash">—</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="card-footer d-flex justify-content-between align-items-center">
            <div class="text-muted small">
                <span id="permCount">0</span> permisos marcados
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save mr-1"></i> Guardar permisos
            </button>
        </div>
    </form>
</div>

<script>
(function () {
    const searchInput = document.getElementById('permSearch');
    const tbody = document.getElementById('permTbody');
    const checks = () => Array.from(document.querySelectorAll('.perm-check'));
    const countEl = document.getElementById('permCount');

    function updateCount() {
        const total = checks().filter(c => c.checked).length;
        countEl.textContent = total;
    }

    // Filtro por módulo (client-side)
    searchInput?.addEventListener('input', function () {
        const term = (this.value || '').toLowerCase().trim();
        Array.from(tbody.querySelectorAll('tr[data-module]')).forEach(tr => {
            const hay = (tr.getAttribute('data-module') || '');
            tr.style.display = (term === '' || hay.includes(term)) ? '' : 'none';
        });
    });

    // Marcar / desmarcar visibles
    document.getElementById('btnSelectAll')?.addEventListener('click', function () {
        Array.from(tbody.querySelectorAll('tr')).forEach(tr => {
            if (tr.style.display === 'none') return;
            Array.from(tr.querySelectorAll('.perm-check')).forEach(c => c.checked = true);
        });
        updateCount();
    });

    document.getElementById('btnUnselectAll')?.addEventListener('click', function () {
        Array.from(tbody.querySelectorAll('tr')).forEach(tr => {
            if (tr.style.display === 'none') return;
            Array.from(tr.querySelectorAll('.perm-check')).forEach(c => c.checked = false);
        });
        updateCount();
    });

    // Marcar / desmarcar fila
    document.querySelectorAll('.btnRowAll').forEach(btn => {
        btn.addEventListener('click', () => {
            const rowKey = btn.getAttribute('data-row');
            const tr = tbody.querySelector(`tr[data-module*="${rowKey}"]`);
            if (!tr) return;
            Array.from(tr.querySelectorAll('.perm-check')).forEach(c => c.checked = true);
            updateCount();
        });
    });

    document.querySelectorAll('.btnRowNone').forEach(btn => {
        btn.addEventListener('click', () => {
            const rowKey = btn.getAttribute('data-row');
            const tr = tbody.querySelector(`tr[data-module*="${rowKey}"]`);
            if (!tr) return;
            Array.from(tr.querySelectorAll('.perm-check')).forEach(c => c.checked = false);
            updateCount();
        });
    });

    // Contador live
    document.addEventListener('change', function (e) {
        if (e.target && e.target.classList.contains('perm-check')) {
            updateCount();
        }
    });

    updateCount();
})();
</script>
@endsection

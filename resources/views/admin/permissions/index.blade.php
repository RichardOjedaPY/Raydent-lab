@extends('layouts.admin')

@section('title', 'Permisos')

@section('content_header')
    <div class="d-flex align-items-center justify-content-between flex-wrap">
        <div>
            <h1 class="m-0">Permisos</h1>
            <div class="text-muted small">Mapa de permisos por rol</div>
        </div>

        <div class="d-flex align-items-center gap-2 mt-2 mt-md-0">
            <div class="input-group input-group-sm" style="min-width: 280px;">
                <div class="input-group-prepend">
                    <span class="input-group-text bg-white">
                        <i class="fas fa-search text-muted"></i>
                    </span>
                </div>
                <input id="permSearch" type="text" class="form-control" placeholder="Buscar permiso o rol...">
                <div class="input-group-append">
                    <button id="btnClearSearch" class="btn btn-outline-secondary" type="button">Limpiar</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')

    @php
        // Agrupamos por módulo: todo lo antes del primer punto.
        // Ej: "usuarios.view" => "usuarios"
        $grouped = $permissions
            ->groupBy(fn ($p) => \Illuminate\Support\Str::before($p->name, '.'))
            ->sortKeys();
    @endphp

    <style>
        /* Chips */
        .chip {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .25rem .6rem;
            border-radius: 999px;
            font-size: .78rem;
            line-height: 1;
            border: 1px solid rgba(0,0,0,.08);
            white-space: nowrap;
        }
        .chip i { font-size: .75rem; opacity: .9; }

        .chip-role {
            background: rgba(23, 162, 184, .10);
            border-color: rgba(23, 162, 184, .25);
            color: #0f5e6b;
        }
        .chip-none {
            background: rgba(108,117,125,.10);
            border-color: rgba(108,117,125,.25);
            color: #555;
        }

        code.perm-code {
            padding: .2rem .45rem;
            border-radius: .35rem;
            background: rgba(0,0,0,.04);
            border: 1px solid rgba(0,0,0,.06);
            font-size: .82rem;
        }

        .count-pill {
            border-radius: 999px;
            padding: .15rem .5rem;
            font-size: .75rem;
            border: 1px solid rgba(0,0,0,.12);
            background: #fff;
            color: rgba(0,0,0,.65);
        }

        /* Tabla más limpia */
        .table-modern thead th {
            border-top: 0;
            border-bottom: 1px solid rgba(0,0,0,.08) !important;
            font-size: .78rem;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: rgba(0,0,0,.55);
        }
        .table-modern tbody tr:hover {
            background: rgba(0,123,255,.035);
        }

        /* Accordion con <details> (no depende de JS de Bootstrap) */
        .perm-group {
            border: 1px solid rgba(0,0,0,.08);
            border-radius: .75rem;
            background: #fff;
            overflow: hidden;
        }
        .perm-group + .perm-group { margin-top: .75rem; }

        .perm-group summary {
            list-style: none;
            cursor: pointer;
            user-select: none;
            padding: .9rem 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            background: linear-gradient(180deg, rgba(0,0,0,.02), rgba(0,0,0,.00));
            border-bottom: 1px solid rgba(0,0,0,.06);
        }
        .perm-group summary::-webkit-details-marker { display: none; }

        .perm-group .summary-left {
            display: flex;
            align-items: center;
            gap: .6rem;
            min-width: 0;
        }
        .perm-group .group-title {
            font-weight: 700;
            color: rgba(0,0,0,.80);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .perm-group .chev {
            transition: transform .2s ease;
            color: rgba(0,0,0,.45);
        }
        .perm-group[open] .chev {
            transform: rotate(180deg);
        }
        .perm-group .group-meta {
            display: flex;
            align-items: center;
            gap: .5rem;
            flex-wrap: wrap;
        }

        .perm-group .group-body {
            padding: 0;
        }
    </style>

    {{-- CARD: Accesos rápidos por rol --}}
    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex align-items-center justify-content-between flex-wrap">
            <div class="d-flex align-items-center">
                <i class="fas fa-user-shield text-primary mr-2"></i>
                <strong>Configurar permisos por rol</strong>
                <span class="ml-2 count-pill">{{ $roles->count() }} roles</span>
            </div>
            <div class="text-muted small mt-2 mt-md-0">
                Entrá a cada rol para asignar/desasignar permisos.
            </div>
        </div>

        <div class="card-body">
            @foreach($roles as $r)
                @php
                    $label = match ($r->name) {
                        'clinica' => 'Clínica',
                        'tecnico' => 'Técnico',
                        default   => ucfirst($r->name),
                    };

                    $btnClass = match ($r->name) {
                        'admin', 'superadmin' => 'btn-outline-danger',
                        'tecnico'             => 'btn-outline-info',
                        'clinica'             => 'btn-outline-primary',
                        default               => 'btn-outline-secondary',
                    };
                @endphp

                <a href="{{ route('admin.permissions.edit-role', $r) }}"
                   class="btn btn-sm {{ $btnClass }} mr-2 mb-2"
                   style="border-radius:999px;">
                    <i class="fas fa-sliders-h mr-1"></i> {{ $label }}
                </a>
            @endforeach
        </div>
    </div>

    {{-- CARD: Permisos agrupados --}}
    <div class="card shadow-sm mt-3">
        <div class="card-header bg-white d-flex align-items-center justify-content-between flex-wrap">
            <div class="d-flex align-items-center">
                <i class="fas fa-key text-primary mr-2"></i>
                <strong>Listado de permisos</strong>
                <span class="ml-2 count-pill" id="permCount">{{ $permissions->count() }} permisos</span>
            </div>

            <div class="text-muted small mt-2 mt-md-0">
                Agrupados por módulo. Buscá por <b>permiso</b> o <b>rol</b>.
            </div>
        </div>

        <div class="card-body">
            {{-- Empty state al filtrar --}}
            <div id="emptyFilterState" class="text-center text-muted p-4 d-none">
                <i class="fas fa-search mr-1"></i> No se encontraron permisos con ese filtro.
            </div>

            @forelse($grouped as $group => $perms)
                @php
                    $groupLabel = \Illuminate\Support\Str::title(str_replace(['_', '-'], ' ', $group));
                    $groupId = 'grp_' . preg_replace('/[^a-z0-9_]/i', '_', $group);
                @endphp

                <details class="perm-group perm-group-box" data-group="{{ strtolower($group) }}" open>
                    <summary>
                        <div class="summary-left">
                            <i class="fas fa-layer-group text-primary"></i>
                            <div class="group-title">
                                {{ $groupLabel }}
                                <span class="text-muted font-weight-normal">({{ $group }})</span>
                            </div>
                        </div>

                        <div class="group-meta">
                            <span class="count-pill group-count" data-group-count="{{ strtolower($group) }}">
                                {{ $perms->count() }} permisos
                            </span>
                            <i class="fas fa-chevron-down chev"></i>
                        </div>
                    </summary>

                    <div class="group-body table-responsive">
                        <table class="table table-modern mb-0 table-sm">
                            <thead>
                                <tr>
                                    <th style="width: 40%;">Permiso</th>
                                    <th>Roles que lo tienen</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($perms as $perm)
                                    @php
                                        $rolesText = $perm->roles->map(function ($role) {
                                            return match ($role->name) {
                                                'clinica' => 'Clínica',
                                                'tecnico' => 'Técnico',
                                                default   => ucfirst($role->name),
                                            };
                                        })->implode(' ');
                                    @endphp

                                    <tr class="perm-row"
                                        data-group="{{ strtolower($group) }}"
                                        data-perm="{{ strtolower($perm->name) }}"
                                        data-roles="{{ strtolower($rolesText) }}">
                                        <td>
                                            <code class="perm-code">{{ $perm->name }}</code>
                                        </td>
                                        <td>
                                            @if($perm->roles->isEmpty())
                                                <span class="chip chip-none">
                                                    <i class="fas fa-minus-circle"></i> Sin roles
                                                </span>
                                            @else
                                                @foreach($perm->roles as $role)
                                                    @php
                                                        $label = match ($role->name) {
                                                            'clinica' => 'Clínica',
                                                            'tecnico' => 'Técnico',
                                                            default   => ucfirst($role->name),
                                                        };

                                                        $icon = match ($role->name) {
                                                            'admin', 'superadmin' => 'fas fa-crown',
                                                            'tecnico'             => 'fas fa-user-cog',
                                                            'clinica'             => 'fas fa-hospital',
                                                            default               => 'fas fa-user-tag',
                                                        };
                                                    @endphp

                                                    <span class="chip chip-role mr-1 mb-1">
                                                        <i class="{{ $icon }}"></i> {{ $label }}
                                                    </span>
                                                @endforeach
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </details>
            @empty
                <div class="text-center text-muted p-4">
                    <i class="far fa-folder-open mr-1"></i> No hay permisos registrados.
                </div>
            @endforelse
        </div>
    </div>

    <script>
        (function () {
            const input = document.getElementById('permSearch');
            const btnClear = document.getElementById('btnClearSearch');

            const rows   = Array.from(document.querySelectorAll('.perm-row'));
            const groups = Array.from(document.querySelectorAll('.perm-group-box'));
            const totalCountEl = document.getElementById('permCount');
            const empty = document.getElementById('emptyFilterState');

            function applyFilter() {
                const q = (input.value || '').trim().toLowerCase();
                let totalVisible = 0;

                // 1) Filtrar filas
                rows.forEach(tr => {
                    const perm  = tr.dataset.perm || '';
                    const roles = tr.dataset.roles || '';
                    const ok = !q || perm.includes(q) || roles.includes(q);

                    tr.style.display = ok ? '' : 'none';
                    if (ok) totalVisible++;
                });

                // 2) Actualizar grupos (ocultar grupos sin filas visibles + contador por grupo)
                groups.forEach(g => {
                    const gkey = g.dataset.group || '';
                    const gRows = rows.filter(r => (r.dataset.group || '') === gkey);
                    const visibleInGroup = gRows.reduce((acc, r) => acc + (r.style.display === 'none' ? 0 : 1), 0);

                    // contador por grupo
                    const pill = g.querySelector(`[data-group-count="${gkey}"]`);
                    if (pill) pill.textContent = `${visibleInGroup} permisos`;

                    // ocultar grupo si no tiene visibles
                    g.style.display = visibleInGroup > 0 ? '' : 'none';

                    // si está buscando, abrir solo grupos con resultados
                    if (q) {
                        g.open = visibleInGroup > 0;
                    }
                });

                // 3) Contador total + empty state
                totalCountEl.textContent = `${totalVisible} permisos`;
                empty.classList.toggle('d-none', totalVisible !== 0 || rows.length === 0);
            }

            input?.addEventListener('input', applyFilter);
            btnClear?.addEventListener('click', () => {
                input.value = '';
                applyFilter();

                // al limpiar, volver a mostrar y abrir grupos
                groups.forEach(g => { g.style.display = ''; g.open = true; });

                input.focus();
            });

            applyFilter();
        })();
    </script>
@endsection

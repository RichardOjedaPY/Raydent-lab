 {{-- resources/views/layouts/partials/sidebar.blade.php --}}
<aside class="main-sidebar sidebar-dark-primary elevation-4">

    @php
        $u = auth()->user();

        // ✅ Dashboard dinámico por rol (admin / tecnico / clinica)
        $dashboardRoute = 'dashboard'; // fallback al redirect inteligente

        if ($u) {
            if ($u->hasRole('admin')) {
                $dashboardRoute = 'admin.dashboard';
            } elseif ($u->hasRole('tecnico')) {
                $dashboardRoute = 'admin.tecnico.dashboard';
            } elseif ($u->hasRole('clinica')) {
                $dashboardRoute = 'admin.clinica.dashboard';
            } else {
                // fallback por permisos (por si existen usuarios sin rol)
                if ($u->can('tecnico.pedidos.view')) {
                    $dashboardRoute = 'admin.tecnico.dashboard';
                } elseif ($u->can('pedidos.view')) {
                    $dashboardRoute = 'admin.pedidos.index';
                }
            }
        }

        $dashboardActive =
            request()->routeIs('dashboard') ||
            request()->routeIs('admin.dashboard') ||
            request()->routeIs('admin.tecnico.dashboard') ||
            request()->routeIs('admin.clinica.dashboard');
    @endphp

    {{-- Logo / marca --}}
    <a href="{{ route($dashboardRoute) }}" class="brand-link d-flex align-items-center">
        <img src="{{ asset('img/raydent-logo.png') }}" alt="Raydent" class="brand-image img-circle elevation-0 mr-2"
             style="opacity:.95; width:34px; height:34px; object-fit:contain;">
        <span class="brand-text font-weight-semibold">Raydent Lab</span>
    </a>

    <div class="sidebar">
        {{-- Usuario --}}
        @auth
            <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                <div class="image">
                    <i class="fas fa-user-circle fa-2x"></i>
                </div>
                <div class="info">
                    <a href="#" class="d-block">{{ Auth::user()->name }}</a>
                    <div class="text-muted small">
                        {{ Auth::user()->getRoleNames()->first() ?? '' }}
                    </div>
                </div>
            </div>
        @endauth

        {{-- Menú --}}
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column"
                data-widget="treeview"
                role="menu"
                data-accordion="false">

                {{-- Dashboard (según rol) --}}
                <li class="nav-item">
                    <a href="{{ route($dashboardRoute) }}" class="nav-link {{ $dashboardActive ? 'active' : '' }}">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Dashboard</p>
                    </a>
                </li>

                {{-- Auditoría --}}
                @can('activity_logs.view')
                    <li class="nav-item">
                        <a href="{{ route('admin.activity-logs.index') }}"
                           class="nav-link {{ request()->routeIs('admin.activity-logs.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-history"></i>
                            <p>Auditoría</p>
                        </a>
                    </li>
                @endcan

                {{-- Usuarios --}}
                @can('usuarios.view')
                    <li class="nav-item">
                        <a href="{{ route('admin.usuarios.index') }}"
                           class="nav-link {{ request()->routeIs('admin.usuarios.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-users"></i>
                            <p>Usuarios</p>
                        </a>
                    </li>
                @endcan

                {{-- Clínicas --}}
                @can('clinicas.view')
                    <li class="nav-item">
                        <a href="{{ route('admin.clinicas.index') }}"
                           class="nav-link {{ request()->routeIs('admin.clinicas.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-hospital"></i>
                            <p>Clínicas</p>
                        </a>
                    </li>
                @endcan

                {{-- Permisos / Roles (solo admin) --}}
                @role('admin')
                    <li class="nav-item">
                        <a href="{{ route('admin.permissions.index') }}"
                           class="nav-link {{ request()->routeIs('admin.permissions.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-key"></i>
                            <p>Permisos</p>
                        </a>
                    </li>
                @endrole

                {{-- Pacientes --}}
                @can('pacientes.view')
                    <li class="nav-item">
                        <a href="{{ route('admin.pacientes.index') }}"
                           class="nav-link {{ request()->routeIs('admin.pacientes.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-user-injured"></i>
                            <p>Pacientes</p>
                        </a>
                    </li>
                @endcan

                {{-- Consultas --}}
                @can('consultas.view')
                    <li class="nav-item">
                        <a href="{{ route('admin.consultas.index') }}"
                           class="nav-link {{ request()->routeIs('admin.consultas.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-notes-medical"></i>
                            <p>Consultas</p>
                        </a>
                    </li>
                @endcan

                {{-- Pedidos (se oculta para Técnico; técnico trabaja desde Panel Técnico) --}}
                @php($u = auth()->user())
                @if ($u && !$u->hasRole('tecnico'))
                    @can('pedidos.view')
                        <li class="nav-item">
                            <a href="{{ route('admin.pedidos.index') }}"
                               class="nav-link {{ request()->routeIs('admin.pedidos.*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-file-medical"></i>
                                <p>Pedidos</p>
                            </a>
                        </li>
                    @endcan
                @endif

                {{-- Panel Técnico (rol o permiso) --}}
                @if (auth()->check() &&
                        (auth()->user()->hasAnyRole(['tecnico', 'admin']) ||
                         auth()->user()->can('tecnico.pedidos.view')))
                    <li class="nav-item">
                        <a href="{{ route('admin.tecnico.pedidos.index') }}"
                           class="nav-link {{ request()->routeIs('admin.tecnico.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-tools"></i>
                            <p>Panel Técnico</p>
                        </a>
                    </li>
                @endif

                {{-- =========================
                     TARIFARIO (Precio base)
                     Opción A: maestro global
                     ========================= --}}
                     @can('tarifario.view')
                     <li class="nav-header text-uppercase" style="opacity:.75;">Gestión de Precios</li>
                 
                     {{-- Maestro Global --}}
                     <li class="nav-item">
                         <a href="{{ route('admin.tarifario.index') }}"
                            class="nav-link {{ request()->routeIs('admin.tarifario.index') ? 'active' : '' }}">
                             <i class="nav-icon fas fa-globe"></i>
                             <p>Tarifario Maestro</p>
                         </a>
                     </li>
                 
                     {{-- Tarifario por Clínica --}}
                     <li class="nav-item">
                         <a href="{{ route('admin.tarifario.clinica.index') }}"
                            class="nav-link {{ request()->routeIs('admin.tarifario.clinica*') ? 'active' : '' }}">
                             <i class="nav-icon fas fa-clinic-medical"></i>
                             <p>Precios por Clínica</p>
                         </a>
                     </li>  
                 @endcan

            </ul>
        </nav>
    </div>
</aside>

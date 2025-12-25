 {{-- resources/views/layouts/partials/sidebar.blade.php --}}
 <aside class="main-sidebar sidebar-dark-primary elevation-4">

    @php
    $u = auth()->user();

    // ✅ Dashboard dinámico por rol (admin / tecnico / clinica / cajero)
    $dashboardRoute = 'dashboard'; // fallback al redirect inteligente

    if ($u) {
        if ($u->hasRole('admin')) {
            $dashboardRoute = 'admin.dashboard';
        } elseif ($u->hasRole('tecnico')) {
            $dashboardRoute = 'admin.tecnico.dashboard';
        } elseif ($u->hasRole('clinica')) {
            $dashboardRoute = 'admin.clinica.dashboard';
        } elseif ($u->hasRole('cajero')) {
            $dashboardRoute = 'admin.cajero.dashboard';
        } else {
            // fallback por permisos (por si existen usuarios sin rol)
            if ($u->can('tecnico.pedidos.view')) {
                $dashboardRoute = 'admin.tecnico.dashboard';
            } elseif ($u->can('pagos.view') || $u->can('pagos.create') || $u->can('liquidaciones.view')) {
                $dashboardRoute = 'admin.cajero.dashboard';
            } elseif ($u->can('pedidos.view')) {
                $dashboardRoute = 'admin.pedidos.index';
            }
        }
    }

    $dashboardActive =
        request()->routeIs('dashboard') ||
        request()->routeIs('admin.dashboard') ||
        request()->routeIs('admin.tecnico.dashboard') ||
        request()->routeIs('admin.clinica.dashboard') ||
        request()->routeIs('admin.cajero.dashboard');

    // ✅ Flags/actives para menús agrupados
    $tarifarioActive = request()->routeIs('admin.tarifario.*');

    // Cobros (pagos/liquidaciones/estado-cuenta) + dashboard cajero
    $cobrosActive =
        request()->routeIs('admin.liquidaciones.*') ||
        request()->routeIs('admin.pagos.*') ||
        request()->routeIs('admin.estado_cuenta.*') ||
        request()->routeIs('admin.cajero.dashboard');

    $showTarifario =
        $u &&
        $u->can('tarifario.view') &&
        (\Route::has('admin.tarifario.index') || \Route::has('admin.tarifario.clinica.index'));

    $showCobros =
        $u &&
        (
            $u->can('liquidaciones.view') ||
            $u->can('pagos.view') ||
            $u->can('pagos.create') ||
            $u->hasRole('cajero') // ✅ cajero entra aunque uses solo rol
        ) &&
        (
            \Route::has('admin.liquidaciones.pedidos_liquidados') ||
            \Route::has('admin.pagos.multiple.create') ||
            \Route::has('admin.pagos.show') ||
            \Route::has('admin.estado_cuenta.index') ||
            \Route::has('admin.cajero.dashboard')
        );
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
             <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
                 data-accordion="false">

                 {{-- Dashboard (según rol) --}}
                 <li class="nav-item">
                     <a href="{{ route($dashboardRoute) }}" class="nav-link {{ $dashboardActive ? 'active' : '' }}">
                         <i class="nav-icon fas fa-tachometer-alt"></i>
                         <p>Dashboard</p>
                     </a>
                 </li>

                 {{-- =========================
                    ADMINISTRACIÓN
                ========================= --}}
                 @if (auth()->check() &&
                         (auth()->user()->can('activity_logs.view') ||
                             auth()->user()->can('usuarios.view') ||
                             auth()->user()->can('clinicas.view') ||
                             auth()->user()->hasRole('admin')))
                     <li class="nav-header text-uppercase" style="opacity:.75;">Administración</li>
                 @endif

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

                 {{-- =========================
                    OPERACIÓN
                ========================= --}}
                 @if (auth()->check() &&
                         (auth()->user()->can('pacientes.view') ||
                             auth()->user()->can('consultas.view') ||
                             auth()->user()->can('pedidos.view') ||
                             auth()->user()->hasAnyRole(['tecnico', 'admin']) ||
                             auth()->user()->can('tecnico.pedidos.view')))
                     <li class="nav-header text-uppercase" style="opacity:.75;">Operación</li>
                 @endif

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
                    FINANZAS (Tarifario + Cobros)
                ========================= --}}
                 @if ($showTarifario || $showCobros)
                     <li class="nav-header text-uppercase" style="opacity:.75;">Finanzas</li>

                     {{-- ===== Tarifario ===== --}}
                     @if ($showTarifario)
                         <li class="nav-item has-treeview {{ $tarifarioActive ? 'menu-open' : '' }}">
                             <a href="#" class="nav-link {{ $tarifarioActive ? 'active' : '' }}">
                                 <i class="nav-icon fas fa-tags"></i>
                                 <p>
                                     Tarifario
                                     <i class="right fas fa-angle-left"></i>
                                 </p>
                             </a>

                             <ul class="nav nav-treeview">
                                 @if (\Route::has('admin.tarifario.index'))
                                     <li class="nav-item">
                                         <a href="{{ route('admin.tarifario.index') }}"
                                             class="nav-link {{ request()->routeIs('admin.tarifario.index') ? 'active' : '' }}">
                                             <i class="far fa-circle nav-icon"></i>
                                             <p>Tarifario Maestro</p>
                                         </a>
                                     </li>
                                 @endif

                                 @if (\Route::has('admin.tarifario.clinica.index'))
                                     <li class="nav-item">
                                         <a href="{{ route('admin.tarifario.clinica.index') }}"
                                             class="nav-link {{ request()->routeIs('admin.tarifario.clinica*') ? 'active' : '' }}">
                                             <i class="far fa-circle nav-icon"></i>
                                             <p>Precios por Clínica</p>
                                         </a>
                                     </li>
                                 @endif
                             </ul>
                         </li>
                     @endif

                     {{-- ===== Cobros ===== --}}
                     @if ($showCobros)
                         <li class="nav-item has-treeview {{ $cobrosActive ? 'menu-open' : '' }}">
                             <a href="#" class="nav-link {{ $cobrosActive ? 'active' : '' }}">
                                 <i class="nav-icon fas fa-file-invoice-dollar"></i>
                                 <p>
                                     Cobros
                                     <i class="right fas fa-angle-left"></i>
                                 </p>
                             </a>

                             <ul class="nav nav-treeview">
                                 @can('liquidaciones.view')
                                     @if (\Route::has('admin.liquidaciones.pedidos_liquidados'))
                                         <li class="nav-item">
                                             <a href="{{ route('admin.liquidaciones.pedidos_liquidados') }}"
                                                 class="nav-link {{ request()->routeIs('admin.liquidaciones.pedidos_liquidados') ? 'active' : '' }}">
                                                 <i class="far fa-circle nav-icon"></i>
                                                 <p>Pedidos liquidados</p>
                                             </a>
                                         </li>
                                     @endif
                                 @endcan
                                 {{-- Cobro múltiple (si existe la ruta) --}}
                                 {{-- Cobro múltiple (si existe la ruta) --}}
                                 @can('pagos.create')
                                     @if (\Route::has('admin.pagos.multiple.create'))
                                         <li class="nav-item">
                                             <a href="{{ route('admin.pagos.multiple.create') }}"
                                                 class="nav-link {{ request()->routeIs('admin.pagos.multiple.*') ? 'active' : '' }}">
                                                 <i class="far fa-circle nav-icon"></i>
                                                 <p>Cobro múltiple</p>
                                             </a>
                                         </li>
                                     @endif
                                 @endcan



                                 {{-- Listado de pagos (si existe) --}}
                                 @can('pagos.view')
                                     @if (\Route::has('admin.pagos.index'))
                                         <li class="nav-item">
                                             <a href="{{ route('admin.pagos.index') }}"
                                                 class="nav-link {{ request()->routeIs('admin.pagos.*') ? 'active' : '' }}">
                                                 <i class="far fa-circle nav-icon"></i>
                                                 <p>Pagos</p>
                                             </a>
                                         </li>
                                     @endif
                                 @endcan
                                 @can('estado_cuenta.view')
                                     <li class="nav-item">
                                         <a href="{{ route('admin.estado_cuenta.index') }}"
                                             class="nav-link {{ request()->routeIs('admin.estado_cuenta.*') ? 'active' : '' }}">
                                             <i class="far fa-circle nav-icon"></i>
                                             <p>Estado de cuenta</p>
                                         </a>
                                     </li>
                                 @endcan

                             </ul>
                         </li>
                     @endif
                 @endif

             </ul>
         </nav>
     </div>
 </aside>

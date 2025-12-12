 {{-- resources/views/layouts/partials/sidebar.blade.php --}}
 <aside class="main-sidebar sidebar-dark-primary elevation-4">
     {{-- Logo / marca --}}
     <a href="{{ route('admin.dashboard') }}" class="brand-link d-flex align-items-center">
         <img src="{{ asset('img/raydent-logo.png') }}" alt="Raydent" class="brand-image img-circle elevation-0 mr-2"
             style="opacity: .95; width: 34px; height: 34px; object-fit: contain;">
         <span class="brand-text font-weight-semibold">
             Raydent Lab
         </span>
     </a>

     {{-- Sidebar --}}
     <div class="sidebar">
         {{-- Usuario --}}
         @auth
             <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                 <div class="image">
                     <i class="fas fa-user-circle fa-2x"></i>
                 </div>
                 <div class="info">
                     <a href="#" class="d-block">{{ Auth::user()->name }}</a>
                 </div>
             </div>
         @endauth

         {{-- Menú --}}
         <nav class="mt-2">
             <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
                 data-accordion="false">

                 {{-- Dashboard (solo ver si tiene permiso a algo, de momento lo dejamos libre para admin) --}}
                 <li class="nav-item">
                     <a href="{{ route('admin.dashboard') }}"
                         class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                         <i class="nav-icon fas fa-tachometer-alt"></i>
                         <p>Dashboard</p>
                     </a>
                 </li>

                 {{-- Usuarios: requiere permiso usuarios.view --}}
                 @can('usuarios.view')
                     <li class="nav-item">
                         <a href="{{ route('admin.usuarios.index') }}"
                             class="nav-link {{ request()->routeIs('admin.usuarios.*') ? 'active' : '' }}">
                             <i class="nav-icon fas fa-users"></i>
                             <p>Usuarios</p>
                         </a>
                     </li>
                 @endcan

                 {{-- Clínicas: requiere permiso clinicas.view --}}
                 @can('clinicas.view')
                     <li class="nav-item">
                         <a href="{{ route('admin.clinicas.index') }}"
                             class="nav-link {{ request()->routeIs('admin.clinicas.*') ? 'active' : '' }}">
                             <i class="nav-icon fas fa-hospital"></i>
                             <p>Clínicas</p>
                         </a>
                     </li>
                 @endcan

                 {{-- Permisos: solo admin, ya está protegido por middleware role:admin --}}
                 <li class="nav-item">
                     <a href="{{ route('admin.permissions.index') }}"
                         class="nav-link {{ request()->routeIs('admin.permissions.*') ? 'active' : '' }}">
                         <i class="nav-icon fas fa-key"></i>
                         <p>Permisos</p>
                     </a>
                 </li>


             </ul>
         </nav>
     </div>
 </aside>

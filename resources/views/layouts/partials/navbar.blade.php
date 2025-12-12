 {{-- resources/views/layouts/partials/navbar.blade.php --}}
<nav class="main-header navbar navbar-expand navbar-dark raydent-navbar">

    {{-- Botón para ocultar/mostrar sidebar --}}
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button">
                <i class="fas fa-bars"></i>
            </a>
        </li>

        {{-- Título pequeño del panel --}}
        <li class="nav-item d-none d-sm-inline-block">
            <span class="nav-link text-sm">
                Panel del laboratorio Raydent
            </span>
        </li>
    </ul>

    {{-- Menú derecho --}}
    <ul class="navbar-nav ml-auto">

        @auth
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <i class="far fa-user"></i>
                    <span class="ml-1 text-sm">{{ Auth::user()->name }}</span>
                    <i class="fas fa-angle-down ml-1 text-xs"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-right raydent-dropdown">
                    <span class="dropdown-item-text text-sm">
                        {{ Auth::user()->email }}
                    </span>
                    <div class="dropdown-divider"></div>

                    {{-- Logout (Breeze) --}}
                    <form action="{{ route('logout') }}" method="POST" class="mb-0">
                        @csrf
                        <button type="submit" class="dropdown-item text-sm text-danger">
                            <i class="fas fa-sign-out-alt mr-2"></i> Cerrar sesión
                        </button>
                    </form>
                </div>
            </li>
        @endauth

    </ul>
</nav>

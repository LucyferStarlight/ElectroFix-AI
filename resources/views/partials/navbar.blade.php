<nav class="navbar navbar-expand-lg navbar-glass border-bottom">
    <div class="container-fluid px-3 px-lg-4">
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="{{ route('landing') }}">
            <span class="brand-mark">EF</span>
            <span>ELECTRO<span class="text-accent">FIX</span>-AI</span>
        </a>

        @auth
            @php($user = auth()->user())
            <div class="dropdown d-lg-none">
                <button
                    class="navbar-toggler border-0 mobile-menu-trigger"
                    type="button"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                    aria-label="Abrir menu"
                >
                    <span class="navbar-toggler-icon"></span>
                </button>

                <ul class="dropdown-menu dropdown-menu-end p-2 mobile-nav-dropdown">
                    <li class="dropdown-header px-3 py-2">{{ $user->name }} · {{ $user->role }}</li>

                    <li><hr class="dropdown-divider my-2"></li>
                    <li class="dropdown-header px-3 py-1">Dashboard</li>
                    <li><a class="dropdown-item rounded-3 px-3 py-2" href="{{ route('dashboard') }}">Inicio</a></li>

                    @if(in_array($user->role, ['worker', 'admin', 'developer'], true))
                        <li><hr class="dropdown-divider my-2"></li>
                        <li class="dropdown-header px-3 py-1">Operaci&oacute;n</li>
                        <li><a class="dropdown-item rounded-3 px-3 py-2" href="{{ route('worker.orders') }}">&Oacute;rdenes</a></li>
                        <li><a class="dropdown-item rounded-3 px-3 py-2" href="{{ route('worker.customers') }}">Clientes</a></li>
                        <li><a class="dropdown-item rounded-3 px-3 py-2" href="{{ route('worker.equipments') }}">Equipos</a></li>
                        @if($user->canAccessModule('inventory'))
                            <li><a class="dropdown-item rounded-3 px-3 py-2" href="{{ route('worker.inventory') }}">Inventario</a></li>
                        @endif
                        @if($user->canAccessModule('billing'))
                            <li><a class="dropdown-item rounded-3 px-3 py-2" href="{{ route('worker.billing') }}">Facturaci&oacute;n</a></li>
                        @endif
                    @endif

                    @if($user->role === 'admin')
                        <li><hr class="dropdown-divider my-2"></li>
                        <li class="dropdown-header px-3 py-1">Administraci&oacute;n</li>
                        <li><a class="dropdown-item rounded-3 px-3 py-2" href="{{ route('dashboard.admin') }}">Panel Admin</a></li>
                        <li><a class="dropdown-item rounded-3 px-3 py-2" href="{{ route('admin.technicians.index') }}">T&eacute;cnicos</a></li>
                        <li><a class="dropdown-item rounded-3 px-3 py-2" href="{{ route('admin.company.edit') }}">Datos Empresa</a></li>
                        <li><a class="dropdown-item rounded-3 px-3 py-2" href="{{ route('admin.subscription.edit') }}">Suscripci&oacute;n</a></li>
                    @endif

                    @if($user->role === 'developer')
                        <li><hr class="dropdown-divider my-2"></li>
                        <li class="dropdown-header px-3 py-1">Developer</li>
                        <li><a class="dropdown-item rounded-3 px-3 py-2" href="{{ route('dashboard.developer') }}">Panel Developer</a></li>
                        <li><a class="dropdown-item rounded-3 px-3 py-2" href="{{ route('developer.companies.index') }}">Empresas</a></li>
                        <li><a class="dropdown-item rounded-3 px-3 py-2" href="{{ route('developer.subscriptions') }}">Suscripciones</a></li>
                        <li><a class="dropdown-item rounded-3 px-3 py-2" href="{{ route('developer.test-company') }}">Empresa Test</a></li>
                    @endif

                    <li><hr class="dropdown-divider my-2"></li>
                    <li class="px-3 py-2">
                        <button class="btn btn-ui btn-ghost btn-sm w-100 text-start" type="button" data-ui-theme-toggle>
                            <span data-ui-theme-toggle-label>Modo claro</span>
                        </button>
                    </li>
                    <li><a class="dropdown-item rounded-3 px-3 py-2" href="{{ route('support') }}">Soporte</a></li>
                    <li class="px-3 py-2">
                        <form method="post" action="{{ route('logout') }}" class="m-0">
                            @csrf
                            <button type="submit" class="btn btn-ui btn-outline-ui btn-sm w-100">Salir</button>
                        </form>
                    </li>
                </ul>
            </div>
        @endauth

        <div class="navbar-collapse d-none d-lg-flex">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                @auth
                    <li class="nav-item"><a class="nav-link" href="{{ route('dashboard') }}">Dashboard</a></li>
                    @if(auth()->user()->role === 'admin')
                        <li class="nav-item"><a class="nav-link" href="{{ route('admin.workers.index') }}">Workers</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('admin.company.edit') }}">Empresa</a></li>
                    @endif
                    @if(auth()->user()->role === 'developer')
                        <li class="nav-item"><a class="nav-link" href="{{ route('developer.companies.index') }}">Empresas</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('developer.subscriptions') }}">Suscripciones</a></li>
                    @endif
                    <li class="nav-item"><a class="nav-link" href="{{ route('support') }}">Soporte</a></li>
                @endauth
            </ul>

            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-ui btn-ghost btn-sm" type="button" data-ui-theme-toggle>
                    <span data-ui-theme-toggle-label>Cambiar tema</span>
                </button>
                @auth
                    <span class="small text-muted">{{ auth()->user()->name }} · {{ auth()->user()->role }}</span>
                    <form method="post" action="{{ route('logout') }}" class="m-0">
                        @csrf
                        <button type="submit" class="btn btn-ui btn-outline-ui btn-sm">Salir</button>
                    </form>
                @else
                    <a class="btn btn-ui btn-primary-ui btn-sm" href="{{ route('login') }}">Acceso</a>
                    <a class="btn btn-ui btn-outline-ui btn-sm" href="{{ route('register') }}">Crear cuenta</a>
                    <a class="btn btn-ui btn-ghost btn-sm" href="{{ route('support') }}">Soporte</a>
                @endauth
            </div>
        </div>
    </div>
</nav>

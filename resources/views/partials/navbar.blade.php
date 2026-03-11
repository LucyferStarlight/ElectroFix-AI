<nav class="navbar navbar-expand-lg navbar-glass border-bottom">
    <div class="container-fluid px-3 px-lg-4">
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="{{ route('landing') }}">
            <span class="brand-mark">EF</span>
            <span>ELECTRO<span class="text-accent">FIX</span>-AI</span>
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNavbar">
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

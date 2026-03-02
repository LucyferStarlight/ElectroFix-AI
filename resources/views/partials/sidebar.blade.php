<aside class="saas-sidebar border-end">
    <div class="sidebar-brand px-3 px-lg-4 py-4">
        <div class="d-flex align-items-center gap-2">
            <span class="brand-mark">EF</span>
            <div>
                <p class="mb-0 fw-bold">ElectroFix-AI</p>
                @auth
                    <small class="text-muted">{{ auth()->user()->role }} session</small>
                @else
                    <small class="text-muted">Control Center</small>
                @endauth
            </div>
        </div>
    </div>

    <nav class="px-3 px-lg-4 pb-4">
        @auth
            @php($user = auth()->user())

            <p class="sidebar-section">Dashboard</p>
            <a class="sidebar-link" href="{{ route('dashboard') }}">Inicio</a>

            @if(in_array($user->role, ['worker', 'admin', 'developer'], true))
                <p class="sidebar-section mt-4">Operación</p>
                <a class="sidebar-link" href="{{ route('worker.orders') }}">Órdenes</a>
                <a class="sidebar-link" href="{{ route('worker.customers') }}">Clientes</a>
                <a class="sidebar-link" href="{{ route('worker.equipments') }}">Equipos</a>
                @if($user->canAccessModule('inventory'))
                    <a class="sidebar-link" href="{{ route('worker.inventory') }}">Inventario</a>
                @endif
                @if($user->canAccessModule('billing'))
                    <a class="sidebar-link" href="{{ route('worker.billing') }}">Facturación</a>
                @endif
            @endif

            @if($user->role === 'admin')
                <p class="sidebar-section mt-4">Administración</p>
                <a class="sidebar-link" href="{{ route('dashboard.admin') }}">Panel Admin</a>
                <a class="sidebar-link" href="{{ route('admin.workers.index') }}">Trabajadores</a>
                <a class="sidebar-link" href="{{ route('admin.company.edit') }}">Datos Empresa</a>
                <a class="sidebar-link" href="{{ route('admin.subscription.edit') }}">Suscripción</a>
            @endif

            @if($user->role === 'developer')
                <p class="sidebar-section mt-4">Developer</p>
                <a class="sidebar-link" href="{{ route('dashboard.developer') }}">Panel Developer</a>
                <a class="sidebar-link" href="{{ route('developer.companies.index') }}">Empresas</a>
                <a class="sidebar-link" href="{{ route('developer.subscriptions') }}">Suscripciones</a>
                <a class="sidebar-link" href="{{ route('developer.test-company') }}">Empresa Test</a>
            @endif
        @endauth
    </nav>
</aside>

@extends('layouts.guest')

@section('title', 'ElectroFix-AI | Software para talleres de electrodomésticos')

@section('content')
<style>
    body.guest-layout > nav.navbar-glass,
    body.guest-layout > footer.border-top {
        display: none !important;
    }

    body.guest-layout .page-shell {
        padding-top: 0 !important;
        padding-bottom: 0 !important;
        min-height: 100vh;
    }

    #landing-navbar {
        background: rgba(255, 255, 255, 0.94);
        backdrop-filter: blur(10px);
    }

    #landingNavbarContent {
        transition: background-color 0.2s ease, border-color 0.2s ease;
    }

    #landing-hero {
        padding-top: 7rem;
        padding-bottom: 5rem;
    }

    #landing-hero .display-4,
    #landing-final h2 {
        letter-spacing: -0.03em;
    }

    #hero-mockup,
    #final-cta-card,
    #pricing-toggle,
    [data-feature-card],
    [data-problem-card],
    [data-plan-card] {
        border-radius: 1.25rem;
    }

    #hero-mockup {
        background:
            radial-gradient(circle at top right, rgba(14, 165, 233, 0.18), transparent 35%),
            linear-gradient(180deg, rgba(14, 165, 233, 0.08), rgba(255, 255, 255, 1));
    }

    [data-feature-card][data-highlight="true"] {
        border-color: rgba(14, 165, 233, 0.35);
        box-shadow: var(--ef-shadow);
    }

    [data-period-button].active {
        background-color: var(--ef-accent);
        border-color: var(--ef-accent);
        color: #fff;
    }

    [data-price-value] {
        letter-spacing: -0.03em;
    }

    body.theme-dark #landing-navbar {
        background: rgba(15, 23, 42, 0.92);
        border-color: var(--ef-border) !important;
    }

    body.theme-dark #landing-navbar .navbar-toggler {
        color: var(--ef-text);
        border-color: var(--ef-border);
    }

    #landing-navbar .dropdown-menu {
        min-width: 16rem;
        border-radius: 1rem;
        border-color: var(--ef-border);
        background-color: var(--ef-surface);
        box-shadow: var(--ef-shadow);
    }

    #landing-navbar .dropdown-item {
        color: var(--ef-text);
    }

    #landing-navbar .dropdown-item:hover,
    #landing-navbar .dropdown-item:focus {
        background-color: var(--ef-surface-soft);
        color: var(--ef-text);
    }

    #landing-navbar .dropdown-header {
        color: var(--ef-text-muted);
    }

    @media (max-width: 991.98px) {
        #landing-hero {
            padding-top: 6rem;
        }

        #landingNavbarContent {
            margin-top: 0.75rem;
            padding-top: 1rem;
            border-top: 1px solid var(--ef-border);
        }
    }
</style>

<nav id="landing-navbar" class="navbar navbar-expand-lg fixed-top border-bottom shadow-sm">
    <div class="container py-2">
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="{{ route('landing') }}">
            <span class="brand-mark">EF</span>
            <span>ELECTRO<span class="text-accent">FIX</span>-AI</span>
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#landingNavbarContent" aria-controls="landingNavbarContent" aria-expanded="false" aria-label="Abrir navegación">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="landingNavbarContent">
            <ul class="navbar-nav mx-auto mb-3 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="#problema">Problema</a></li>
                <li class="nav-item"><a class="nav-link" href="#solucion">Solución</a></li>
                <li class="nav-item"><a class="nav-link" href="#precios">Precios</a></li>
                <li class="nav-item"><a class="nav-link" href="#faq">FAQ</a></li>
            </ul>

            <div class="d-flex flex-column flex-lg-row gap-2 align-items-lg-center">
                <button class="btn btn-ui btn-ghost" type="button" data-ui-theme-toggle>
                    <span data-ui-theme-toggle-label>Modo claro</span>
                </button>
                @if(auth()->check())
                    @php
                        $user = auth()->user();
                    @endphp

                    <div class="dropdown">
                        <button
                            class="btn btn-ui btn-outline-ui dropdown-toggle d-inline-flex align-items-center gap-2"
                            type="button"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                        >
                            <i class="bi bi-person-circle"></i>
                            <span class="d-lg-inline">{{ $user->name }}</span>
                        </button>

                        <ul class="dropdown-menu dropdown-menu-end p-2">
                            <li class="dropdown-header px-3 py-2">{{ $user->role }} · {{ $user->email }}</li>
                            <li><a class="dropdown-item rounded-3 px-3 py-2" href="{{ route('dashboard') }}">Dashboard</a></li>

                            @if(in_array($user->role, ['worker', 'admin', 'developer'], true))
                                <li><a class="dropdown-item rounded-3 px-3 py-2" href="{{ route('worker.orders') }}">Órdenes</a></li>
                                <li><a class="dropdown-item rounded-3 px-3 py-2" href="{{ route('worker.customers') }}">Clientes</a></li>
                                <li><a class="dropdown-item rounded-3 px-3 py-2" href="{{ route('worker.equipments') }}">Equipos</a></li>
                                @if($user->canAccessModule('inventory'))
                                    <li><a class="dropdown-item rounded-3 px-3 py-2" href="{{ route('worker.inventory') }}">Inventario</a></li>
                                @endif
                                @if($user->canAccessModule('billing'))
                                    <li><a class="dropdown-item rounded-3 px-3 py-2" href="{{ route('worker.billing') }}">Facturación</a></li>
                                @endif
                            @endif

                            @if($user->role === 'admin')
                                <li><hr class="dropdown-divider my-2"></li>
                                <li><a class="dropdown-item rounded-3 px-3 py-2" href="{{ route('admin.technicians.index') }}">Técnicos</a></li>
                                <li><a class="dropdown-item rounded-3 px-3 py-2" href="{{ route('admin.company.edit') }}">Empresa</a></li>
                                <li><a class="dropdown-item rounded-3 px-3 py-2" href="{{ route('admin.subscription.edit') }}">Suscripción</a></li>
                            @endif

                            @if($user->role === 'developer')
                                <li><hr class="dropdown-divider my-2"></li>
                                <li><a class="dropdown-item rounded-3 px-3 py-2" href="{{ route('developer.companies.index') }}">Empresas</a></li>
                                <li><a class="dropdown-item rounded-3 px-3 py-2" href="{{ route('developer.subscriptions') }}">Suscripciones</a></li>
                                <li><a class="dropdown-item rounded-3 px-3 py-2" href="{{ route('developer.test-company') }}">Empresa Test</a></li>
                            @endif

                            <li><hr class="dropdown-divider my-2"></li>
                            <li><a class="dropdown-item rounded-3 px-3 py-2" href="{{ route('support') }}">Soporte</a></li>
                        </ul>
                    </div>

                    <form method="post" action="{{ route('logout') }}" class="m-0">
                        @csrf
                        <button type="submit" class="btn btn-ui btn-primary-ui">Cerrar sesión</button>
                    </form>
                @else
                    <a class="btn btn-ui btn-outline-ui" href="{{ route('login') }}">Iniciar sesión</a>
                    <a class="btn btn-ui btn-primary-ui" href="{{ route('register') }}">Registrar taller</a>
                @endif
            </div>
        </div>
    </div>
</nav>

<section id="landing-hero">
    <div class="container">
        @if(request()->query('checkout') === 'success')
            <div class="alert alert-success mb-4">Pago completado. Tu empresa quedará activa cuando Stripe confirme el cobro.</div>
        @elseif(request()->query('checkout') === 'cancel')
            <div class="alert alert-warning mb-4">El pago fue cancelado. Puedes retomarlo cuando quieras.</div>
        @endif

        <div class="row align-items-center g-4 g-xl-5">
            <div class="col-lg-6">
                <span class="badge badge-ui badge-ui-info mb-3">IA de diagnóstico incluida en todos los planes</span>
                <h1 class="display-4 fw-bold mb-3">Menos papel, menos Excel y más control real para tu taller de electrodomésticos.</h1>
                <p class="lead text-muted mb-4">ElectroFix-AI centraliza órdenes, clientes, equipos, inventario y facturación en un solo sistema. Tu equipo trabaja con procesos claros y ARIS te ayuda a diagnosticar más rápido desde el primer día.</p>
                <div class="d-flex flex-column flex-sm-row gap-3 mb-4">
                    <a class="btn btn-ui btn-primary-ui btn-lg" href="{{ route('register') }}">Registrar mi taller</a>
                    <a class="btn btn-ui btn-outline-ui btn-lg" href="#precios">Ver planes</a>
                </div>
                <div class="row g-3 text-muted small">
                    <div class="col-sm-4">
                        <div class="card card-ui h-100">
                            <div class="card-body">
                                <div class="fw-bold text-dark mb-1">Multiempresa</div>
                                <div>Cada taller opera aislado y con sus propios datos.</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="card card-ui h-100">
                            <div class="card-body">
                                <div class="fw-bold text-dark mb-1">Roles claros</div>
                                <div>Admin delega permisos, Worker ejecuta la operación diaria.</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="card card-ui h-100">
                            <div class="card-body">
                                <div class="fw-bold text-dark mb-1">Onboarding ágil</div>
                                <div>Registro, pago y activación sin procesos manuales.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div id="hero-mockup" class="card card-ui shadow-soft border-0">
                    <div class="card-body p-4 p-xl-5">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <div class="text-uppercase text-muted small fw-semibold">Panel operativo</div>
                                <h2 class="h4 fw-bold mb-0">Taller Centro</h2>
                            </div>
                            <span class="badge badge-ui badge-ui-success">IA disponible</span>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <div class="card card-ui h-100">
                                    <div class="card-body">
                                        <div class="text-muted small mb-1">Órdenes activas</div>
                                        <div class="fs-2 fw-bold">12</div>
                                        <div class="small text-success">4 listas para entrega</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="card card-ui h-100">
                                    <div class="card-body">
                                        <div class="text-muted small mb-1">Técnicos</div>
                                        <div class="fs-2 fw-bold">3</div>
                                        <div class="small text-muted">2 en diagnóstico, 1 en reparación</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card card-ui mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="fw-semibold">ARIS sugirió diagnóstico</div>
                                    <i class="bi bi-cpu text-info fs-5"></i>
                                </div>
                                <p class="text-muted small mb-2">Refrigerador con enfriamiento irregular y compresor forzado.</p>
                                <div class="small mb-2"><strong>Causa probable:</strong> relevador de arranque fatigado y condensador fuera de rango.</div>
                                <div class="small"><strong>Estimado:</strong> $1,350 MXN a $1,650 MXN</div>
                            </div>
                        </div>

                        <div class="card card-ui">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="fw-semibold">Estado del día</div>
                                    <span class="small text-muted">Actualizado hace 2 min</span>
                                </div>
                                <div class="d-flex justify-content-between small py-2 border-bottom">
                                    <span>Refacciones con stock bajo</span>
                                    <strong>5</strong>
                                </div>
                                <div class="d-flex justify-content-between small py-2 border-bottom">
                                    <span>Correos enviados a clientes</span>
                                    <strong>9</strong>
                                </div>
                                <div class="d-flex justify-content-between small pt-2">
                                    <span>PDF generados hoy</span>
                                    <strong>7</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="problema" class="py-5">
    <div class="container">
        <div class="text-center mx-auto mb-5" style="max-width: 760px;">
            <span class="badge badge-ui badge-ui-info mb-3">El caos del taller sin sistema</span>
            <h2 class="fw-bold mb-3">Cuando la operación vive en papel, mensajes sueltos y archivos de Excel, el taller pierde tiempo y credibilidad.</h2>
            <p class="text-muted mb-0">ElectroFix-AI está diseñado para atacar los puntos que más frenan la operación diaria de un taller técnico.</p>
        </div>

        <div class="row g-4">
            <div class="col-md-6 col-xl-3">
                <div data-problem-card class="card card-ui shadow-soft h-100">
                    <div class="card-body p-4">
                        <div class="mb-3 fs-3 text-info"><i class="bi bi-file-earmark-text"></i></div>
                        <h3 class="h5 fw-bold">Órdenes que se pierden</h3>
                        <p class="text-muted mb-0">Recepciones en papel o notas improvisadas terminan mezcladas, duplicadas o sin seguimiento claro.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div data-problem-card class="card card-ui shadow-soft h-100">
                    <div class="card-body p-4">
                        <div class="mb-3 fs-3 text-info"><i class="bi bi-box-seam"></i></div>
                        <h3 class="h5 fw-bold">Inventario incierto</h3>
                        <p class="text-muted mb-0">No saber qué refacciones tienes ni cuánto stock queda provoca compras urgentes y retrasos en entrega.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div data-problem-card class="card card-ui shadow-soft h-100">
                    <div class="card-body p-4">
                        <div class="mb-3 fs-3 text-info"><i class="bi bi-receipt-cutoff"></i></div>
                        <h3 class="h5 fw-bold">Tickets poco claros</h3>
                        <p class="text-muted mb-0">Anotaciones ilegibles o incompletas generan reclamos, confusión y mala experiencia para el cliente.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div data-problem-card class="card card-ui shadow-soft h-100">
                    <div class="card-body p-4">
                        <div class="mb-3 fs-3 text-info"><i class="bi bi-clipboard-pulse"></i></div>
                        <h3 class="h5 fw-bold">Diagnósticos inconsistentes</h3>
                        <p class="text-muted mb-0">Cada técnico evalúa distinto y eso complica presupuestos, tiempos de reparación y confianza del cliente.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="solucion" class="py-5">
    <div class="container">
        <div class="text-center mx-auto mb-5" style="max-width: 760px;">
            <span class="badge badge-ui badge-ui-success mb-3">Lo que ElectroFix-AI resuelve</span>
            <h2 class="fw-bold mb-3">Una sola plataforma para ordenar la operación del taller y tomar decisiones con mejor información.</h2>
            <p class="text-muted mb-0">Desde la recepción del equipo hasta la facturación final, cada módulo está pensado para el flujo real de un taller de electrodomésticos.</p>
        </div>

        <div class="row g-4">
            <div class="col-md-6 col-xl-4">
                <div data-feature-card class="card card-ui h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <i class="bi bi-clipboard-check fs-3 text-info"></i>
                            <span class="badge badge-ui badge-ui-info">Operación</span>
                        </div>
                        <h3 class="h5 fw-bold">Órdenes digitales</h3>
                        <p class="text-muted mb-0">Controla el flujo completo desde recepción, diagnóstico y asignación, hasta entrega final del equipo.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div data-feature-card class="card card-ui h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <i class="bi bi-tools fs-3 text-info"></i>
                            <span class="badge badge-ui badge-ui-info">Almacén</span>
                        </div>
                        <h3 class="h5 fw-bold">Inventario de refacciones</h3>
                        <p class="text-muted mb-0">Mantén stock real con movimientos de entrada y salida para evitar faltantes o compras innecesarias.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div data-feature-card class="card card-ui h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <i class="bi bi-file-earmark-pdf fs-3 text-info"></i>
                            <span class="badge badge-ui badge-ui-info">Cobro</span>
                        </div>
                        <h3 class="h5 fw-bold">Facturación integrada</h3>
                        <p class="text-muted mb-0">Genera PDF para órdenes, ventas directas, ventas mixtas y reparaciones desde el mismo sistema.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div data-feature-card data-highlight="true" class="card card-ui h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <i class="bi bi-cpu fs-3 text-info"></i>
                            <span class="badge badge-ui badge-ui-success">Nuevo</span>
                        </div>
                        <h3 class="h5 fw-bold">IA de diagnóstico ARIS</h3>
                        <p class="text-muted mb-0">Describe síntomas y recibe causas probables, acciones sugeridas y estimado de costo con tecnología Groq.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div data-feature-card class="card card-ui h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <i class="bi bi-person-gear fs-3 text-info"></i>
                            <span class="badge badge-ui badge-ui-info">Equipo</span>
                        </div>
                        <h3 class="h5 fw-bold">Control de roles</h3>
                        <p class="text-muted mb-0">El Admin gestiona la empresa, delega permisos y el Worker se concentra en la operación del taller.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div data-feature-card class="card card-ui h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <i class="bi bi-envelope-check fs-3 text-info"></i>
                            <span class="badge badge-ui badge-ui-info">Automático</span>
                        </div>
                        <h3 class="h5 fw-bold">Notificaciones automáticas al cliente</h3>
                        <p class="text-muted mb-0">Cuando cambia el estado de una orden, el cliente recibe un correo sin que el técnico tenga que hacer pasos extra.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="precios" class="py-5">
    <div class="container">
        <div class="text-center mx-auto mb-5" style="max-width: 760px;">
            <span class="badge badge-ui badge-ui-info mb-3">Planes para cada etapa del taller</span>
            <h2 class="fw-bold mb-3">Empieza con el plan que se ajusta a tu operación y cambia de periodo cuando te convenga.</h2>
            <p class="text-muted mb-4">Todos los planes incluyen acceso web, empresa aislada, onboarding automatizado y ARIS para apoyar el diagnóstico técnico.</p>

            <div id="pricing-toggle" class="btn-group p-1 card card-ui shadow-soft" role="group" aria-label="Seleccionar periodo de facturación">
                <button type="button" class="btn btn-ui active" data-period-button="monthly">Mensual</button>
                <button type="button" class="btn btn-ui" data-period-button="semiannual">Semestral</button>
                <button type="button" class="btn btn-ui" data-period-button="annual">Anual</button>
            </div>
        </div>

        <div class="row g-4">
            @foreach ($plans as $key => $plan)
                @php
                    $isPopular = $key === 'pro';
                    $monthly = (float) data_get($plan, 'prices.monthly.amount', 0);
                    $semiannual = (float) data_get($plan, 'prices.semiannual.amount', 0);
                    $annual = (float) data_get($plan, 'prices.annual.amount', 0);
                @endphp

                <div class="col-lg-4">
                    <div data-plan-card class="card card-ui h-100 shadow-soft {{ $isPopular ? 'border-primary' : '' }}"
                        data-plan="{{ $key }}"
                        data-label="{{ $plan['label'] }}"
                        data-monthly="{{ $monthly }}"
                        data-semiannual="{{ $semiannual }}"
                        data-annual="{{ $annual }}">
                        <div class="card-body p-4 p-xl-5 d-flex flex-column">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h3 class="h4 fw-bold mb-0">{{ $plan['label'] }}</h3>
                                @if($isPopular)
                                    <span class="badge badge-ui badge-ui-success">Más popular</span>
                                @endif
                            </div>

                            <p class="text-muted mb-4">Ideal para talleres que quieren controlar órdenes, equipo y cobros sin depender de procesos manuales.</p>

                            <div class="mb-4">
                                <div class="d-flex align-items-end gap-2 mb-1">
                                    <span class="fs-1 fw-bold" data-price-value></span>
                                    <span class="text-muted fw-semibold" data-price-suffix>/ mes</span>
                                </div>
                                <div class="small text-muted" data-price-caption></div>
                            </div>

                            <ul class="list-unstyled d-grid gap-2 mb-4">
                                @foreach ($plan['features'] as $feature)
                                    <li class="d-flex align-items-start gap-2">
                                        <i class="bi bi-check-circle-fill text-success mt-1"></i>
                                        <span>{{ $feature }}</span>
                                    </li>
                                @endforeach
                            </ul>

                            <div class="mt-auto d-grid">
                                <a
                                    class="btn btn-ui {{ $isPopular ? 'btn-primary-ui' : 'btn-outline-ui' }}"
                                    data-register-link
                                    href="{{ route('register', ['plan' => $key, 'period' => 'monthly']) }}"
                                >
                                    Registrarme en {{ $plan['label'] }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section id="faq" class="py-5">
    <div class="container">
        <div class="text-center mx-auto mb-5" style="max-width: 760px;">
            <span class="badge badge-ui badge-ui-info mb-3">Preguntas frecuentes</span>
            <h2 class="fw-bold mb-3">Respuestas claras antes de registrar tu taller.</h2>
            <p class="text-muted mb-0">Si quieres resolver algo más específico, también puedes escribirnos desde soporte.</p>
        </div>

        <div class="accordion accordion-flush card card-ui shadow-soft overflow-hidden" id="landingFaq">
            <div class="accordion-item">
                <h3 class="accordion-header">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faqOne" aria-expanded="true" aria-controls="faqOne">
                        ¿Mis datos están separados de los de otros talleres?
                    </button>
                </h3>
                <div id="faqOne" class="accordion-collapse collapse show" data-bs-parent="#landingFaq">
                    <div class="accordion-body text-muted">
                        Sí. ElectroFix-AI opera en modo multiempresa, así que cada taller trabaja con su propia información, usuarios, órdenes, clientes e inventario de forma aislada.
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h3 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqTwo" aria-expanded="false" aria-controls="faqTwo">
                        ¿Qué es ARIS y cómo funciona el diagnóstico con IA?
                    </button>
                </h3>
                <div id="faqTwo" class="accordion-collapse collapse" data-bs-parent="#landingFaq">
                    <div class="accordion-body text-muted">
                        ARIS es el asistente de diagnóstico de ElectroFix-AI. El técnico describe síntomas del equipo y recibe un análisis con causas probables, acciones sugeridas y un estimado de costo para orientar la revisión.
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h3 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqThree" aria-expanded="false" aria-controls="faqThree">
                        ¿Necesito instalar algo para usar ElectroFix-AI?
                    </button>
                </h3>
                <div id="faqThree" class="accordion-collapse collapse" data-bs-parent="#landingFaq">
                    <div class="accordion-body text-muted">
                        No. Es una plataforma web, así que solo necesitas conexión a internet y un navegador para entrar, administrar tu taller y trabajar con tu equipo.
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h3 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqFour" aria-expanded="false" aria-controls="faqFour">
                        ¿Qué pasa si cancelo mi suscripción?
                    </button>
                </h3>
                <div id="faqFour" class="accordion-collapse collapse" data-bs-parent="#landingFaq">
                    <div class="accordion-body text-muted">
                        Tu acceso queda sujeto al estado de la suscripción. Si decides cancelar, podrás reactivar tu taller más adelante al volver a completar el proceso de pago.
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h3 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqFive" aria-expanded="false" aria-controls="faqFive">
                        ¿Puedo tener varios técnicos en mi taller?
                    </button>
                </h3>
                <div id="faqFive" class="accordion-collapse collapse" data-bs-parent="#landingFaq">
                    <div class="accordion-body text-muted">
                        Sí. Cada plan contempla distintos alcances y puedes trabajar con varios técnicos, asignar órdenes y delegar permisos específicos desde la cuenta del Admin.
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="landing-final" class="py-5">
    <div class="container">
        <div id="final-cta-card" class="card card-ui shadow-soft border-0">
            <div class="card-body p-4 p-lg-5 text-center">
                <span class="badge badge-ui badge-ui-success mb-3">Listo para empezar</span>
                <h2 class="display-6 fw-bold mb-3">Haz que tu taller deje de depender del desorden y empiece a operar con procesos claros.</h2>
                <p class="text-muted mx-auto mb-4" style="max-width: 760px;">Registra tu taller, elige tu plan y activa tu empresa en minutos. ElectroFix-AI te ayuda a controlar órdenes, refacciones, equipo y diagnósticos desde una sola plataforma.</p>
                <div class="d-flex flex-column flex-sm-row justify-content-center gap-3">
                    <a class="btn btn-ui btn-primary-ui btn-lg" href="{{ route('register') }}">Registrar mi taller ahora</a>
                    <a class="btn btn-ui btn-outline-ui btn-lg" href="{{ route('support') }}">Hablar con soporte</a>
                </div>
            </div>
        </div>
    </div>
</section>

<footer class="border-top bg-surface py-4">
    <div class="container">
        <div class="row g-3 align-items-center justify-content-between">
            <div class="col-lg-5">
                <div class="d-flex align-items-center gap-2 fw-bold mb-2">
                    <span class="brand-mark">EF</span>
                    <span>ElectroFix-AI</span>
                </div>
                <p class="text-muted mb-0">Software para talleres de electrodomésticos que necesitan orden, control operativo y mejor respuesta al cliente.</p>
            </div>
            <div class="col-lg-4">
                <div class="d-flex flex-wrap gap-3 justify-content-lg-end">
                    <a class="link-ui" href="{{ route('terms') }}">Términos y Condiciones</a>
                    <a class="link-ui" href="{{ route('support') }}">Soporte</a>
                </div>
            </div>
            <div class="col-lg-3">
                <p class="text-muted mb-0 text-lg-end">Desarrollado por ArakataDevs · © 2026</p>
            </div>
        </div>
    </div>
</footer>

<script>
    (() => {
        const formatter = new Intl.NumberFormat('es-MX', {
            style: 'currency',
            currency: 'MXN',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        });

        const buttons = Array.from(document.querySelectorAll('[data-period-button]'));
        const cards = Array.from(document.querySelectorAll('[data-plan-card]'));
        const registerBaseUrl = @json(route('register'));

        const periodConfig = {
            monthly: {
                suffix: '/ mes',
                caption: () => 'Pago mensual en MXN.',
                equivalent: null,
            },
            semiannual: {
                suffix: '/ semestre',
                caption: (prices) => `Equivale a ${formatter.format(prices.semiannual / 6)} al mes.`,
                equivalent: 6,
            },
            annual: {
                suffix: '/ año',
                caption: (prices) => `Equivale a ${formatter.format(prices.annual / 12)} al mes.`,
                equivalent: 12,
            },
        };

        const setPeriod = (period) => {
            const config = periodConfig[period] || periodConfig.monthly;

            buttons.forEach((button) => {
                button.classList.toggle('active', button.dataset.periodButton === period);
            });

            cards.forEach((card) => {
                const prices = {
                    monthly: Number(card.dataset.monthly || 0),
                    semiannual: Number(card.dataset.semiannual || 0),
                    annual: Number(card.dataset.annual || 0),
                };

                const currentPrice = prices[period] || 0;
                const value = card.querySelector('[data-price-value]');
                const suffix = card.querySelector('[data-price-suffix]');
                const caption = card.querySelector('[data-price-caption]');
                const link = card.querySelector('[data-register-link]');

                if (value) {
                    value.textContent = formatter.format(currentPrice);
                }

                if (suffix) {
                    suffix.textContent = config.suffix;
                }

                if (caption) {
                    caption.textContent = typeof config.caption === 'function' ? config.caption(prices) : '';
                }

                if (link) {
                    const url = new URL(registerBaseUrl, window.location.origin);
                    url.searchParams.set('plan', card.dataset.plan);
                    url.searchParams.set('period', period);
                    link.href = url.toString();
                }
            });
        };

        buttons.forEach((button) => {
            button.addEventListener('click', () => setPeriod(button.dataset.periodButton));
        });

        setPeriod('monthly');
    })();
</script>
@endsection

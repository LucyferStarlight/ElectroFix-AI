@extends('layouts.guest')

@section('title', 'ElectroFix-AI | Plataforma SaaS')

@section('content')
<section class="container py-4 py-lg-5">
    <div class="row align-items-center g-4 g-lg-5">
        <div class="col-lg-7">
            <span class="badge badge-ui badge-ui-info mb-3">SaaS operativo para gestión técnica</span>
            <h1 class="display-5 fw-bold mb-3">Gestiona operaciones técnicas con una interfaz clara y lista para escalar.</h1>
            <p class="lead text-muted mb-4">Base visual para módulos de órdenes, personal, inventario, suscripciones y administración avanzada.</p>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('dashboard') }}" class="btn btn-ui btn-primary-ui">Ver dashboard</a>
                <a href="{{ route('login') }}" class="btn btn-ui btn-outline-ui">Acceso de equipo</a>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card card-ui shadow-soft">
                <div class="card-body p-4">
                    <p class="text-uppercase text-muted fw-semibold small mb-2">Estado del sistema</p>
                    <h2 class="h4 fw-bold mb-3">Arquitectura visual preparada</h2>
                    <ul class="list-unstyled mb-0 d-grid gap-2">
                        <li class="d-flex justify-content-between"><span>Roles</span><span class="badge badge-ui badge-ui-success">Listo</span></li>
                        <li class="d-flex justify-content-between"><span>Suscripción</span><span class="badge badge-ui badge-ui-success">Listo</span></li>
                        <li class="d-flex justify-content-between"><span>Panel administrativo</span><span class="badge badge-ui badge-ui-info">Base UI</span></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="container pb-5">
    <div class="row g-3">
        <div class="col-md-6 col-xl-3">
            <div class="card card-ui h-100">
                <div class="card-body">
                    <h3 class="h6 fw-bold">Diseño sobrio</h3>
                    <p class="text-muted mb-0">Paleta técnica azul-pizarra con contraste para uso intensivo.</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card card-ui h-100">
                <div class="card-body">
                    <h3 class="h6 fw-bold">Navegación SaaS</h3>
                    <p class="text-muted mb-0">Sidebar, topbar y áreas de contenido reutilizables por módulo.</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card card-ui h-100">
                <div class="card-body">
                    <h3 class="h6 fw-bold">Componentes base</h3>
                    <p class="text-muted mb-0">Botones, badges, alertas, tablas y tarjetas estandarizadas.</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card card-ui h-100">
                <div class="card-body">
                    <h3 class="h6 fw-bold">Preparado para backend</h3>
                    <p class="text-muted mb-0">Estructura semántica para integrar lógica sin rehacer estilos.</p>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

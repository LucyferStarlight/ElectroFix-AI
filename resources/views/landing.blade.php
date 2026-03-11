@extends('layouts.guest')

@section('title', 'ElectroFix-AI | Plataforma SaaS')

@section('content')
<section class="container py-4 py-lg-5">
    <div class="row align-items-center g-4 g-lg-5">
        <div class="col-lg-7">
            <span class="badge badge-ui badge-ui-info mb-3">Sistema SaaS para talleres técnicos</span>
            <h1 class="display-5 fw-bold mb-3">Conoce exactamente qué estás comprando antes de comprometerte.</h1>
            <p class="lead text-muted mb-4">ElectroFix-AI centraliza órdenes, clientes, equipos, permisos del personal y control por roles en una sola plataforma preparada para escalar.</p>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('login') }}" class="btn btn-ui btn-primary-ui">Probar acceso</a>
                <a href="{{ route('terms') }}" class="btn btn-ui btn-outline-ui">Ver términos y condiciones</a>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card card-ui shadow-soft">
                <div class="card-body p-4">
                    <p class="text-uppercase text-muted fw-semibold small mb-2">Qué incluye hoy</p>
                    <h2 class="h4 fw-bold mb-3">Plataforma lista para operación</h2>
                    <ul class="list-unstyled mb-0 d-grid gap-2">
                        <li class="d-flex justify-content-between"><span>Dashboards por rol</span><span class="badge badge-ui badge-ui-success">Activo</span></li>
                        <li class="d-flex justify-content-between"><span>Módulos operativos</span><span class="badge badge-ui badge-ui-success">Órdenes / Clientes / Equipos</span></li>
                        <li class="d-flex justify-content-between"><span>Permisos delegables</span><span class="badge badge-ui badge-ui-info">Admin a Workers</span></li>
                        <li class="d-flex justify-content-between"><span>Multiempresa</span><span class="badge badge-ui badge-ui-success">Implementado</span></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="container pb-4">
    <div class="card card-ui">
        <div class="card-body p-4 p-lg-5">
            <h2 class="h4 fw-bold mb-4">Cómo funciona el sistema en la práctica</h2>
            <div class="row g-3">
                <div class="col-md-6 col-xl-3">
                    <div class="p-3 rounded-4 border h-100">
                        <p class="small text-muted mb-1">Paso 1</p>
                        <h3 class="h6 fw-bold">Registro de clientes</h3>
                        <p class="text-muted mb-0">Tu equipo captura datos de contacto y mantiene historial comercial ordenado.</p>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="p-3 rounded-4 border h-100">
                        <p class="small text-muted mb-1">Paso 2</p>
                        <h3 class="h6 fw-bold">Alta de equipos</h3>
                        <p class="text-muted mb-0">Cada electrodoméstico queda vinculado a su cliente, marca, modelo y serie.</p>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="p-3 rounded-4 border h-100">
                        <p class="small text-muted mb-1">Paso 3</p>
                        <h3 class="h6 fw-bold">Órdenes de trabajo</h3>
                        <p class="text-muted mb-0">Creación, seguimiento por estatus y panel de detalle con presupuesto y diagnóstico.</p>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="p-3 rounded-4 border h-100">
                        <p class="small text-muted mb-1">Paso 4</p>
                        <h3 class="h6 fw-bold">Control por roles</h3>
                        <p class="text-muted mb-0">Admin define permisos especiales para delegar inventario/facturación a workers.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="container pb-4">
    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card card-ui h-100">
                <div class="card-body">
                    <h3 class="h5 fw-bold">Rol Worker</h3>
                    <p class="text-muted">Operación técnica diaria con acceso a órdenes, clientes y equipos.</p>
                    <ul class="small text-muted mb-0">
                        <li>Flujo de atención y seguimiento</li>
                        <li>Consulta rápida de cliente/equipo</li>
                        <li>Permisos extendidos si el admin lo autoriza</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card card-ui h-100">
                <div class="card-body">
                    <h3 class="h5 fw-bold">Rol Administrador</h3>
                    <p class="text-muted">Control total de su empresa, equipo humano y suscripción.</p>
                    <ul class="small text-muted mb-0">
                        <li>Alta/baja y edición de trabajadores</li>
                        <li>Delegación de responsabilidades</li>
                        <li>Gestión de datos de empresa y plan</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="container pb-5">
    <div class="card card-ui shadow-soft">
        <div class="card-body p-4 p-lg-5">
            <div class="row g-3 align-items-center">
                <div class="col-lg-8">
                    <h2 class="h4 fw-bold mb-2">Toma una decisión informada antes de contratar</h2>
                    <p class="text-muted mb-0">Puedes evaluar operación, estructura y alcance del sistema desde el primer acceso. Si se ajusta a tu flujo, avanzas; si no, no te comprometes.</p>
                </div>
                <div class="col-lg-4 d-flex justify-content-lg-end">
                    <a href="{{ route('login') }}" class="btn btn-ui btn-primary-ui">Entrar y evaluar plataforma</a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

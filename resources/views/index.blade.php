@extends('layouts.guest')

@section('title', 'ElectroFix-AI | SaaS para talleres técnicos')

@section('content')
<section class="container pb-4">
    @if(request()->query('checkout') === 'success')
        <div class="alert alert-success">Pago completado. En unos momentos activaremos tu cuenta cuando Stripe confirme el webhook.</div>
    @elseif(request()->query('checkout') === 'cancel')
        <div class="alert alert-warning">Checkout cancelado. Puedes volver a intentarlo cuando quieras.</div>
    @endif

    <div class="row g-4 align-items-center">
        <div class="col-lg-7">
            <span class="badge badge-ui badge-ui-info mb-3">ElectroFix-AI SaaS</span>
            <h1 class="display-5 fw-bold mb-3">Gestiona tu taller técnico en una sola plataforma.</h1>
            <p class="lead text-muted mb-4">Centraliza órdenes, clientes, inventario, facturación y permisos de tu equipo con un flujo de suscripción real y activación automática.</p>
            <a href="#planes" class="btn btn-ui btn-primary-ui">Ver planes y suscribirme</a>
        </div>
        <div class="col-lg-5">
            <div class="card card-ui shadow-soft">
                <div class="card-body p-4">
                    <h2 class="h5 fw-bold">¿Qué incluye?</h2>
                    <ul class="mb-0 text-muted">
                        <li>Panel operativo por roles.</li>
                        <li>Control de órdenes, clientes y equipos.</li>
                        <li>Inventario y facturación con permisos delegables.</li>
                        <li>Suscripción y cobro automatizado con Stripe.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="container pb-4">
    <div class="card card-ui">
        <div class="card-body p-4 p-lg-5">
            <h2 class="h4 fw-bold mb-3">Cómo funciona</h2>
            <div class="row g-3">
                <div class="col-md-3"><div class="p-3 border rounded-4 h-100"><strong>1.</strong> Elige tu plan.</div></div>
                <div class="col-md-3"><div class="p-3 border rounded-4 h-100"><strong>2.</strong> Te enviamos a Stripe Checkout.</div></div>
                <div class="col-md-3"><div class="p-3 border rounded-4 h-100"><strong>3.</strong> Stripe confirma el pago.</div></div>
                <div class="col-md-3"><div class="p-3 border rounded-4 h-100"><strong>4.</strong> Creamos tu cuenta automáticamente.</div></div>
            </div>
        </div>
    </div>
</section>

<section id="planes" class="container pb-5">
    <div class="row g-3">
        @foreach($plans as $planKey => $plan)
            <div class="col-lg-4">
                <div class="card card-ui h-100">
                    <div class="card-body d-flex flex-column">
                        <h3 class="h5 fw-bold">{{ $plan['label'] ?? ucfirst($planKey) }}</h3>
                        <p class="text-muted">Suscripción en Stripe para empezar a operar tu taller.</p>

                        <form method="post" action="{{ route('subscribe') }}" class="mt-auto d-grid gap-2">
                            @csrf
                            <input type="hidden" name="plan" value="{{ $planKey }}">
                            <div>
                                <label class="form-label">Periodo</label>
                                <select name="billing_period" class="form-select input-ui" required>
                                    @foreach(($plan['prices'] ?? []) as $period => $priceId)
                                        @continue(blank($priceId))
                                        <option value="{{ $period }}">{{ ucfirst($period) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Email administrador</label>
                                <input type="email" name="email" class="form-control input-ui" placeholder="admin@empresa.com" required>
                            </div>
                            <div>
                                <label class="form-label">Nombre de empresa (opcional)</label>
                                <input type="text" name="company_name" class="form-control input-ui" placeholder="Mi Taller Técnico">
                            </div>
                            <div>
                                <label class="form-label">Nombre administrador (opcional)</label>
                                <input type="text" name="admin_name" class="form-control input-ui" placeholder="Juan Pérez">
                            </div>
                            <button class="btn btn-ui btn-primary-ui" type="submit">Suscribirme con Stripe</button>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</section>
@endsection

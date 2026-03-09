@extends('layouts.app')

@section('title', 'Suscripción | ElectroFix-AI')

@section('content')
<div class="container-fluid p-0">
    <div class="card card-ui mb-3">
        <div class="card-body">
            <h1 class="h4 fw-bold mb-2">Suscripción de empresa</h1>
            <p class="text-muted mb-0">Administra plan, periodo de facturación y estado operativo sincronizado con Stripe.</p>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card card-ui h-100"><div class="card-body"><p class="text-muted mb-1">Plan actual</p><h2 class="h5 mb-0 text-uppercase">{{ $subscription?->plan ?? 'N/A' }}</h2></div></div>
        </div>
        <div class="col-md-4">
            <div class="card card-ui h-100"><div class="card-body"><p class="text-muted mb-1">Estado</p><h2 class="h5 mb-0 text-uppercase">{{ $subscription?->status ?? 'N/A' }}</h2></div></div>
        </div>
        <div class="col-md-4">
            <div class="card card-ui h-100"><div class="card-body"><p class="text-muted mb-1">Fin de periodo</p><h2 class="h6 mb-0">{{ $subscription?->current_period_end?->format('Y-m-d H:i') ?? ($subscription?->ends_at?->format('Y-m-d') ?? 'N/A') }}</h2></div></div>
        </div>
    </div>

    @if($pendingChange)
        <div class="alert alert-warning">
            Existe un cambio pendiente hacia <strong>{{ strtoupper($pendingChange->requestedPlan->name ?? 'N/A') }}</strong>
            ({{ strtoupper($pendingChange->requested_billing_period) }})
            efectivo el <strong>{{ $pendingChange->effective_at?->format('Y-m-d H:i') }}</strong>.
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card card-ui h-100">
                <div class="card-body">
                    @env('local')
                        <h2 class="h5 fw-bold mb-3">Crear suscripción base</h2>
                        <form method="post" action="{{ route('admin.subscription.checkout') }}" class="row g-3">
                            @csrf
                            <div class="col-12">
                                <label class="form-label">Plan público</label>
                                <select class="form-select input-ui" name="plan" required>
                                    <option value="">Selecciona plan...</option>
                                    @foreach($plans as $plan)
                                        <option value="{{ $plan->name }}">{{ strtoupper($plan->name) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Periodo</label>
                                <select class="form-select input-ui" name="billing_period" required>
                                    <option value="monthly">Mensual (7 días trial)</option>
                                    <option value="semiannual">Semestral (15 días trial)</option>
                                    <option value="annual">Anual (15 días trial)</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Payment Method ID (Stripe)</label>
                                <input class="form-control input-ui" name="payment_method" placeholder="pm_xxx" required>
                                <small class="text-muted">Usa un método de prueba de Stripe en entorno local.</small>
                            </div>
                            <div class="col-12">
                                <button class="btn btn-ui btn-primary-ui" type="submit">Crear suscripción</button>
                            </div>
                        </form>

                        <hr class="my-4">
                    @endenv

                    <h3 class="h6 fw-bold mb-3">Checkout seguro (Stripe Hosted)</h3>
                    <form method="post" action="{{ route('billing.checkout') }}" class="row g-3">
                        @csrf
                        <div class="col-12">
                            <label class="form-label">Plan público</label>
                            <select class="form-select input-ui" name="plan" required>
                                <option value="">Selecciona plan...</option>
                                @foreach($plans as $plan)
                                    <option value="{{ $plan->name }}">{{ strtoupper($plan->name) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Periodo</label>
                            <select class="form-select input-ui" name="billing_period" required>
                                <option value="monthly">Mensual (7 días trial)</option>
                                <option value="semiannual">Semestral (15 días trial)</option>
                                <option value="annual">Anual (15 días trial)</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-ui btn-outline-ui" type="submit">Ir a Checkout Stripe</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card card-ui h-100">
                <div class="card-body">
                    <h2 class="h5 fw-bold mb-3">Cambiar o cancelar</h2>
                    <form method="post" action="{{ route('admin.subscription.change') }}" class="row g-3 mb-4">
                        @csrf
                        <div class="col-12">
                            <label class="form-label">Nuevo plan</label>
                            <select class="form-select input-ui" name="plan" required>
                                <option value="">Selecciona plan...</option>
                                @foreach($plans as $plan)
                                    <option value="{{ $plan->name }}">{{ strtoupper($plan->name) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Nuevo periodo</label>
                            <select class="form-select input-ui" name="billing_period" required>
                                <option value="monthly">Mensual</option>
                                <option value="semiannual">Semestral</option>
                                <option value="annual">Anual</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-ui btn-outline-ui" type="submit">Solicitar cambio de plan</button>
                        </div>
                    </form>

                    <form method="post" action="{{ route('admin.subscription.cancel') }}">
                        @csrf
                        <button class="btn btn-ui btn-danger" type="submit">Cancelar al final del periodo</button>
                    </form>

                    <hr class="my-4">

                    <h3 class="h6 fw-bold mb-2">Portal de facturación Stripe</h3>
                    <p class="text-muted small mb-3">Gestiona método de pago, historial de facturas y cancelación desde el portal seguro de Stripe.</p>
                    <a href="{{ route('billing.portal') }}" class="btn btn-ui btn-outline-ui">Abrir portal Stripe</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

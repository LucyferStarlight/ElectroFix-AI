@extends('layouts.app')

@section('title', 'Selecciona tu plan | ElectroFix-AI')

@section('content')
<div class="container-fluid p-0">
    <div class="row justify-content-center">
        <div class="col-xl-11">
            <div class="card card-ui shadow-soft mb-3">
                <div class="card-body p-4 p-lg-5">
                    <span class="badge badge-ui badge-ui-warning mb-3">Suscripción requerida</span>
                    <h1 class="h4 fw-bold mb-2">Selecciona un plan para continuar</h1>
                    <p class="text-muted mb-0">
                        Flujo de activación: <strong>selección de plan</strong> → <strong>Stripe Checkout (hosted)</strong> →
                        <strong>webhook</strong> → actualización persistida en <code>company_subscriptions</code>.
                    </p>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <div class="card card-ui h-100">
                        <div class="card-body">
                            <p class="small text-muted mb-1">Plan actual</p>
                            <p class="h5 fw-bold mb-0 text-uppercase">{{ $subscription?->plan ?? 'Sin plan' }}</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-ui h-100">
                        <div class="card-body">
                            <p class="small text-muted mb-1">Estado</p>
                            <p class="h5 fw-bold mb-0 text-uppercase">{{ $subscription?->status ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-ui h-100">
                        <div class="card-body">
                            <p class="small text-muted mb-1">Fin de periodo</p>
                            <p class="h6 mb-0">{{ $subscription?->current_period_end?->format('Y-m-d H:i') ?? 'N/A' }}</p>
                        </div>
                    </div>
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
                @forelse($plans as $plan)
                    <div class="col-lg-4">
                        <div class="card card-ui h-100">
                            <div class="card-body d-flex flex-column">
                                <h2 class="h5 fw-bold text-uppercase">{{ $plan->name }}</h2>
                                <p class="small text-muted mb-3">Selecciona periodo y continúa a Stripe Checkout.</p>

                                <div class="d-grid gap-2 mt-auto">
                                    @foreach($plan->prices as $price)
                                        <form method="post" action="{{ route('billing.checkout') }}">
                                            @csrf
                                            <input type="hidden" name="plan" value="{{ $plan->name }}">
                                            <input type="hidden" name="billing_period" value="{{ $price->billing_period }}">
                                            <button class="btn btn-ui btn-outline-ui w-100" type="submit">
                                                {{ ucfirst($price->billing_period) }} · Trial {{ $price->trial_days }} días
                                            </button>
                                        </form>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="alert alert-warning mb-0">No hay planes públicos disponibles.</div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

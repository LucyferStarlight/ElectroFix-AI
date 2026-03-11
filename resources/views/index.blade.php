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
    <style>
        .plan-period-toggle .btn { min-width: 130px; }
        .plan-period-toggle .btn.is-active { opacity: 0.6; }
        .plan-price { display: flex; align-items: center; gap: 12px; }
        .plan-price .plan-price-strike { opacity: 0.6; text-decoration: line-through; font-size: 0.95rem; }
        .plan-price .plan-price-main { margin: 0 auto; text-align: center; }
        .plan-price .plan-price-current { font-size: 2rem; font-weight: 800; letter-spacing: -0.02em; line-height: 1; }
        .plan-price .plan-price-period { font-size: 0.9rem; color: var(--bs-secondary-color); }
        .plan-card { position: relative; }
    </style>

    <div class="text-center mb-4">
        <h2 class="h4 fw-bold mb-2">Planes y precios</h2>
        <p class="text-muted mb-3">Todos los precios incluyen IVA.</p>
        <div class="btn-group plan-period-toggle" role="group" aria-label="Periodo de pago">
            <button type="button" class="btn btn-ui btn-outline-ui is-active" data-period-button="monthly">Mensual</button>
            <button type="button" class="btn btn-ui btn-outline-ui" data-period-button="semiannual">Semestral</button>
            <button type="button" class="btn btn-ui btn-outline-ui" data-period-button="annual">Anual</button>
        </div>
    </div>

    <div class="row g-3">
        @foreach($plans as $planKey => $plan)
            @php
                $prices = $plan['prices'] ?? [];
                $monthly = data_get($prices, 'monthly.amount');
                $semiannual = data_get($prices, 'semiannual.amount');
                $annual = data_get($prices, 'annual.amount');
            @endphp
            <div class="col-lg-4">
                <div class="card card-ui h-100 plan-card" data-plan-card
                    data-price-monthly="{{ $monthly ?? '' }}"
                    data-price-semiannual="{{ $semiannual ?? '' }}"
                    data-price-annual="{{ $annual ?? '' }}">
                    <div class="card-body d-flex flex-column">
                        <h3 class="h5 fw-bold mb-1">{{ $plan['label'] ?? ucfirst($planKey) }}</h3>
                        <p class="text-muted small mb-3">Suscripción en Stripe para empezar a operar tu taller.</p>

                        <div class="plan-price mb-3">
                            <span class="plan-price-strike d-none" data-plan-strike></span>
                            <div class="plan-price-main">
                                <div class="plan-price-current" data-plan-current></div>
                                <div class="plan-price-period" data-plan-period-label>MXN / mes</div>
                            </div>
                        </div>

                        <ul class="text-muted small mb-4">
                            @foreach(($plan['features'] ?? []) as $feature)
                                <li>{{ $feature }}</li>
                            @endforeach
                        </ul>

                        <div class="mt-auto d-grid gap-2">
                            <input type="hidden" value="monthly" data-billing-period>
                            <a class="btn btn-ui btn-primary-ui" data-register-link data-plan="{{ $planKey }}" href="{{ route('register') }}">
                                Crear cuenta
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</section>

<section class="container pb-5">
    <div class="card card-ui">
        <div class="card-body p-4 p-lg-5 d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <div>
                <h2 class="h4 fw-bold mb-2">Soporte</h2>
                <p class="text-muted mb-0">Estamos listos para ayudarte por correo o WhatsApp.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-ui btn-outline-ui" href="mailto:{{ config('support.email') }}">
                    {{ config('support.email') }}
                </a>
                @if(config('support.whatsapp_url'))
                    <a class="btn btn-ui btn-outline-ui" href="{{ config('support.whatsapp_url') }}" target="_blank" rel="noopener">WhatsApp</a>
                @endif
                <a class="btn btn-ui btn-primary-ui" href="{{ route('support') }}">Formulario de soporte</a>
            </div>
        </div>
    </div>
</section>

<script>
(() => {
    const periodButtons = Array.from(document.querySelectorAll('[data-period-button]'));
    const cards = Array.from(document.querySelectorAll('[data-plan-card]'));
    const formatter = new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    });

    const periodLabels = {
        monthly: 'MXN / mes',
        semiannual: 'MXN / semestre',
        annual: 'MXN / año',
    };

    const setActivePeriod = (period) => {
        periodButtons.forEach((btn) => {
            btn.classList.toggle('is-active', btn.dataset.periodButton === period);
        });

        cards.forEach((card) => {
            const prices = {
                monthly: Number(card.dataset.priceMonthly || 0),
                semiannual: Number(card.dataset.priceSemiannual || 0),
                annual: Number(card.dataset.priceAnnual || 0),
            };

            const current = prices[period] || 0;
            const currentEl = card.querySelector('[data-plan-current]');
            const strikeEl = card.querySelector('[data-plan-strike]');
            const labelEl = card.querySelector('[data-plan-period-label]');
            const billingInput = card.querySelector('[data-billing-period]');
            const registerLink = card.querySelector('[data-register-link]');

            if (currentEl) {
                currentEl.textContent = formatter.format(current);
            }

            if (labelEl) {
                labelEl.textContent = periodLabels[period] || 'MXN / periodo';
            }

            if (billingInput) {
                billingInput.value = period;
            }
            if (registerLink) {
                const plan = registerLink.dataset.plan;
                registerLink.href = `{{ route('register') }}?plan=${encodeURIComponent(plan)}&period=${encodeURIComponent(period)}`;
            }

            if (period === 'semiannual' || period === 'annual') {
                const multiplier = period === 'semiannual' ? 6 : 12;
                const strikeValue = prices.monthly * multiplier;
                if (strikeEl) {
                    strikeEl.textContent = formatter.format(strikeValue);
                    strikeEl.classList.toggle('d-none', strikeValue <= 0);
                }
            } else if (strikeEl) {
                strikeEl.classList.add('d-none');
            }
        });
    };

    periodButtons.forEach((btn) => {
        btn.addEventListener('click', () => setActivePeriod(btn.dataset.periodButton));
    });

    setActivePeriod('monthly');
})();
</script>
@endsection

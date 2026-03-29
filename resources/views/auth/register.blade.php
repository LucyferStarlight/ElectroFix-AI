@extends('layouts.guest')

@section('title', 'Registro | ElectroFix-AI')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-9">
            <div class="card card-ui">
                <div class="card-body p-4 p-lg-5">
                    <h1 class="h4 fw-bold mb-2">Registra tu taller</h1>
                    <p class="text-muted mb-4">Crea tu cuenta, elige un plan y comienza a usar ElectroFix-AI.</p>

                    <form method="post" action="{{ route('register.store') }}">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nombre del taller</label>
                                <input type="text" class="form-control input-ui" name="company_name" value="{{ old('company_name') }}" required>
                                @error('company_name')<small class="text-danger">{{ $message }}</small>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nombre del responsable</label>
                                <input type="text" class="form-control input-ui" name="admin_name" value="{{ old('admin_name') }}" required>
                                @error('admin_name')<small class="text-danger">{{ $message }}</small>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Correo electrónico</label>
                                <input type="email" class="form-control input-ui" name="email" value="{{ old('email') }}" required>
                                @error('email')<small class="text-danger">{{ $message }}</small>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Teléfono (opcional)</label>
                                <input type="text" class="form-control input-ui" name="phone" value="{{ old('phone') }}">
                                @error('phone')<small class="text-danger">{{ $message }}</small>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Contraseña</label>
                                <input type="password" class="form-control input-ui" name="password" required>
                                @error('password')<small class="text-danger">{{ $message }}</small>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Confirmar contraseña</label>
                                <input type="password" class="form-control input-ui" name="password_confirmation" required>
                            </div>
                        </div>

                        <div class="mt-4">
                            <h2 class="h6 fw-bold mb-3">Selecciona tu plan</h2>
                            <div class="btn-group plan-period-toggle mb-3" role="group" aria-label="Periodo de pago">
                                <button type="button" class="btn btn-ui btn-outline-ui" data-period-button="monthly">Mensual</button>
                                <button type="button" class="btn btn-ui btn-outline-ui" data-period-button="semiannual">Semestral</button>
                                <button type="button" class="btn btn-ui btn-outline-ui" data-period-button="annual">Anual</button>
                            </div>
                            <input type="hidden" name="billing_period" value="{{ old('billing_period', $selectedPeriod ?? 'monthly') }}" data-billing-period-input>
                            <div class="row g-3">
                                @foreach($plans as $key => $plan)
                                    @php
                                        $prices = $plan['prices'] ?? [];
                                        $monthly = $prices['monthly'] ?? null;
                                        $semiannual = $prices['semiannual'] ?? null;
                                        $annual = $prices['annual'] ?? null;
                                    @endphp
                                    <div class="col-md-4">
                                        <label class="card card-ui h-100 p-3 border plan-card"
                                               data-plan-card
                                               data-price-monthly="{{ $monthly ?? '' }}"
                                               data-price-semiannual="{{ $semiannual ?? '' }}"
                                               data-price-annual="{{ $annual ?? '' }}"
                                               data-trial-monthly="{{ (int) data_get($plan, 'trial_days.monthly', 0) }}"
                                               data-trial-semiannual="{{ (int) data_get($plan, 'trial_days.semiannual', 0) }}"
                                               data-trial-annual="{{ (int) data_get($plan, 'trial_days.annual', 0) }}">
                                            <div class="d-flex align-items-start gap-2">
                                                <input class="form-check-input mt-1" type="radio" name="plan" value="{{ $key }}" @checked(old('plan', $selectedPlan ?? 'starter') === $key) required>
                                                <div>
                                                    <h3 class="h6 fw-bold mb-1">{{ $plan['label'] }}</h3>
                                                    <p class="text-muted mb-2" data-plan-price-text>Precio a confirmar</p>
                                                    <p class="small mb-2 {{ !empty($trialPromoActive) ? 'text-success' : 'text-muted' }}" data-plan-trial-text>Sin periodo de prueba activo</p>
                                                    <ul class="small text-muted mb-0">
                                                        @foreach($plan['features'] as $feature)
                                                            <li>{{ $feature }}</li>
                                                        @endforeach
                                                        @if($plan['ai_enabled'])
                                                            <li class="text-success">IA diagnóstica con Groq incluida</li>
                                                        @endif
                                                    </ul>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                            @error('plan')<small class="text-danger">{{ $message }}</small>@enderror
                        </div>

                        <div class="form-check mt-4">
                            <input class="form-check-input" type="checkbox" name="terms" value="1" id="terms" required>
                            <label class="form-check-label" for="terms">
                                Acepto los <a href="{{ route('terms') }}" target="_blank">términos y condiciones</a>.
                            </label>
                            @error('terms')<small class="text-danger d-block">{{ $message }}</small>@enderror
                        </div>

                        <button type="submit" class="btn btn-ui btn-primary-ui mt-4">Continuar a pago</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
(() => {
    const periodButtons = Array.from(document.querySelectorAll('[data-period-button]'));
    const cards = Array.from(document.querySelectorAll('[data-plan-card]'));
    const periodInput = document.querySelector('[data-billing-period-input]');
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
    const selectedPeriod = periodInput?.value || 'monthly';
    const trialPromoActive = @json(!empty($trialPromoActive));

    const setActivePeriod = (period) => {
        periodButtons.forEach((btn) => {
            btn.classList.toggle('is-active', btn.dataset.periodButton === period);
        });

        if (periodInput) {
            periodInput.value = period;
        }

        cards.forEach((card) => {
            const prices = {
                monthly: Number(card.dataset.priceMonthly || 0),
                semiannual: Number(card.dataset.priceSemiannual || 0),
                annual: Number(card.dataset.priceAnnual || 0),
            };
            const trialByPeriod = {
                monthly: Number(card.dataset.trialMonthly || 0),
                semiannual: Number(card.dataset.trialSemiannual || 0),
                annual: Number(card.dataset.trialAnnual || 0),
            };
            const current = prices[period] || 0;
            const label = periodLabels[period] || 'MXN / periodo';
            const priceEl = card.querySelector('[data-plan-price-text]');
            const trialEl = card.querySelector('[data-plan-trial-text]');
            if (priceEl) {
                priceEl.textContent = current > 0
                    ? `${formatter.format(current)} ${label}`
                    : 'Precio a confirmar';
            }
            if (trialEl) {
                const days = trialByPeriod[period] || 0;
                if (trialPromoActive && days > 0) {
                    trialEl.textContent = `${days} días de prueba incluidos en este periodo`;
                    trialEl.classList.remove('text-muted');
                    trialEl.classList.add('text-success');
                } else {
                    trialEl.textContent = 'Sin periodo de prueba activo';
                    trialEl.classList.remove('text-success');
                    trialEl.classList.add('text-muted');
                }
            }
        });
    };

    periodButtons.forEach((btn) => {
        btn.addEventListener('click', () => setActivePeriod(btn.dataset.periodButton));
    });

    setActivePeriod(selectedPeriod);
})();
</script>
@endsection

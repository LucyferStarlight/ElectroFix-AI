@extends('layouts.guest')

@section('title', 'Registro de Empresa | ElectroFix-AI')

@section('content')
<section class="container py-4 py-lg-5">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-10">
            <div class="card card-ui shadow-soft">
                <div class="card-body p-4 p-lg-5">
                    <h1 class="h4 fw-bold mb-1">Crear cuenta empresarial</h1>
                    <p class="text-muted mb-4">Registra tu administrador, empresa, workers y plan en un solo flujo.</p>

                    @if($errors->any())
                        <div class="alert alert-ui-warning mb-4">{{ $errors->first() }}</div>
                    @endif

                    <div class="d-flex flex-wrap gap-2 mb-4" id="registerStepsBar">
                        <span class="badge badge-ui badge-ui-info register-step-pill" data-step-pill="1">1. Administrador</span>
                        <span class="badge badge-ui register-step-pill opacity-50" data-step-pill="2">2. Empresa</span>
                        <span class="badge badge-ui register-step-pill opacity-50" data-step-pill="3">3. Workers</span>
                        <span class="badge badge-ui register-step-pill opacity-50" data-step-pill="4">4. Credenciales</span>
                        <span class="badge badge-ui register-step-pill opacity-50" data-step-pill="5">5. Plan y pago</span>
                    </div>

                    <form action="{{ route('register.store') }}" method="post" id="companyRegisterForm" novalidate>
                        @csrf

                        <section data-step="1" class="register-step">
                            <h2 class="h5 fw-bold mb-3">Datos del administrador</h2>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Nombre *</label>
                                    <input class="form-control input-ui" name="admin[name]" value="{{ old('admin.name') }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email *</label>
                                    <input type="email" class="form-control input-ui" name="admin[email]" value="{{ old('admin.email') }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Contraseña *</label>
                                    <input type="password" class="form-control input-ui" name="admin[password]" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Confirmar contraseña *</label>
                                    <input type="password" class="form-control input-ui" name="admin[password_confirmation]" required>
                                </div>
                            </div>
                        </section>

                        <section data-step="2" class="register-step d-none">
                            <h2 class="h5 fw-bold mb-3">Datos mínimos de la empresa</h2>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Nombre comercial *</label>
                                    <input class="form-control input-ui" name="company[name]" value="{{ old('company.name') }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Dirección *</label>
                                    <input class="form-control input-ui" name="company[address_line]" value="{{ old('company.address_line') }}" required>
                                </div>
                            </div>

                            <details class="mt-3">
                                <summary class="fw-semibold">Opcionales de contacto y ubicación</summary>
                                <div class="row g-3 mt-2">
                                    <div class="col-md-4"><label class="form-label">Ciudad</label><input class="form-control input-ui" name="company[city]" value="{{ old('company.city') }}"></div>
                                    <div class="col-md-4"><label class="form-label">Estado</label><input class="form-control input-ui" name="company[state]" value="{{ old('company.state') }}"></div>
                                    <div class="col-md-4"><label class="form-label">País (ISO2)</label><input class="form-control input-ui" name="company[country]" value="{{ old('company.country', 'MX') }}" maxlength="2"></div>
                                    <div class="col-md-4"><label class="form-label">Código postal</label><input class="form-control input-ui" name="company[postal_code]" value="{{ old('company.postal_code') }}"></div>
                                    <div class="col-md-4"><label class="form-label">Teléfono</label><input class="form-control input-ui" name="company[owner_phone]" value="{{ old('company.owner_phone') }}"></div>
                                </div>
                            </details>
                        </section>

                        <section data-step="3" class="register-step d-none">
                            <h2 class="h5 fw-bold mb-3">Cantidad de workers iniciales</h2>
                            <div class="row g-3 align-items-end">
                                <div class="col-md-3">
                                    <label class="form-label">Workers *</label>
                                    <input type="number" class="form-control input-ui" min="0" max="50" id="workersCount" name="workers_count" value="{{ old('workers_count', 0) }}" required>
                                    <small class="text-muted">Máximo recomendado: 50.</small>
                                </div>
                                <div class="col-md-9">
                                    <button class="btn btn-ui btn-outline-ui" type="button" id="generateWorkersRows">Generar filas</button>
                                </div>
                            </div>

                            <div class="table-responsive mt-3">
                                <table class="table table-ui align-middle mb-0" id="workersTable">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Nombre *</th>
                                            <th>Email *</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </section>

                        <section data-step="4" class="register-step d-none">
                            <h2 class="h5 fw-bold mb-3">Credenciales de workers</h2>
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Estrategia de contraseña *</label>
                                    <select class="form-select input-ui" name="worker_password_strategy" id="workerPasswordStrategy" required>
                                        <option value="common" @selected(old('worker_password_strategy', 'common') === 'common')>Contraseña común (editable por worker)</option>
                                        <option value="generated" @selected(old('worker_password_strategy') === 'generated')>Generar segura por worker</option>
                                        <option value="manual" @selected(old('worker_password_strategy') === 'manual')>Manual por worker</option>
                                    </select>
                                </div>
                                <div class="col-md-6" id="commonPasswordWrap">
                                    <label class="form-label">Contraseña común</label>
                                    <input type="password" class="form-control input-ui" name="common_worker_password" id="commonWorkerPassword" minlength="8" value="{{ old('common_worker_password') }}">
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-ui align-middle mb-0" id="workersPasswordTable">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Worker</th>
                                            <th>Password (editable)</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </section>

                        <section data-step="5" class="register-step d-none">
                            <h2 class="h5 fw-bold mb-3">Plan, periodo y simulación de pago</h2>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Plan *</label>
                                    <select class="form-select input-ui" name="subscription[plan]" required>
                                        <option value="starter" @selected(old('subscription.plan', 'starter') === 'starter')>Starter</option>
                                        <option value="pro" @selected(old('subscription.plan') === 'pro')>Pro</option>
                                        <option value="enterprise" @selected(old('subscription.plan') === 'enterprise')>Enterprise</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Periodo *</label>
                                    <select class="form-select input-ui" name="subscription[billing_period]" required>
                                        <option value="monthly" @selected(old('subscription.billing_period', 'monthly') === 'monthly')>Mensual</option>
                                        <option value="semiannual" @selected(old('subscription.billing_period') === 'semiannual')>Semestral</option>
                                        <option value="annual" @selected(old('subscription.billing_period') === 'annual')>Anual</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Periodo de prueba</label>
                                    <select class="form-select input-ui" name="subscription[trial_enabled]" required>
                                        <option value="1" @selected((string) old('subscription.trial_enabled', '1') === '1')>Sí</option>
                                        <option value="0" @selected((string) old('subscription.trial_enabled') === '0')>No</option>
                                    </select>
                                </div>
                            </div>
                            <div class="alert alert-ui-info mt-3 mb-0">
                                Simulación temporal: si no activas trial, el sistema alterna aprobación/rechazo de pago en cada nuevo registro global.
                            </div>
                        </section>

                        <div class="d-flex justify-content-between mt-4">
                            <button type="button" class="btn btn-ui btn-outline-ui" id="prevStepBtn" disabled>Anterior</button>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-ui btn-primary-ui" id="nextStepBtn">Siguiente</button>
                                <button type="submit" class="btn btn-ui btn-primary-ui d-none" id="submitBtn">Completar registro</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
(() => {
    const initialWorkers = @json(old('workers', []));
    const form = document.getElementById('companyRegisterForm');
    const steps = Array.from(document.querySelectorAll('.register-step'));
    const stepPills = Array.from(document.querySelectorAll('[data-step-pill]'));
    const prevBtn = document.getElementById('prevStepBtn');
    const nextBtn = document.getElementById('nextStepBtn');
    const submitBtn = document.getElementById('submitBtn');

    const workersCountInput = document.getElementById('workersCount');
    const generateWorkersRowsBtn = document.getElementById('generateWorkersRows');
    const workersTableBody = document.querySelector('#workersTable tbody');
    const workersPasswordTableBody = document.querySelector('#workersPasswordTable tbody');
    const strategySelect = document.getElementById('workerPasswordStrategy');
    const commonPasswordWrap = document.getElementById('commonPasswordWrap');
    const commonPasswordInput = document.getElementById('commonWorkerPassword');

    let currentStep = 1;

    const randomPassword = () => {
        const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%';
        let out = '';
        for (let i = 0; i < 12; i++) {
            out += chars[Math.floor(Math.random() * chars.length)];
        }
        return out;
    };

    const updateStepState = () => {
        steps.forEach((stepEl) => {
            const stepNo = Number(stepEl.dataset.step);
            stepEl.classList.toggle('d-none', stepNo !== currentStep);
        });

        stepPills.forEach((pill) => {
            const stepNo = Number(pill.dataset.stepPill);
            pill.classList.toggle('opacity-50', stepNo > currentStep);
            pill.classList.toggle('badge-ui-info', stepNo <= currentStep);
        });

        prevBtn.disabled = currentStep === 1;
        nextBtn.classList.toggle('d-none', currentStep === steps.length);
        submitBtn.classList.toggle('d-none', currentStep !== steps.length);
    };

    const buildWorkersRows = () => {
        const count = Math.max(0, Math.min(50, Number(workersCountInput.value || 0)));
        workersTableBody.innerHTML = '';
        workersPasswordTableBody.innerHTML = '';

        for (let i = 0; i < count; i++) {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${i + 1}</td>
                <td><input class="form-control input-ui" name="workers[${i}][name]" required></td>
                <td><input type="email" class="form-control input-ui" name="workers[${i}][email]" required></td>
            `;
            workersTableBody.appendChild(tr);

            const pwTr = document.createElement('tr');
            pwTr.innerHTML = `
                <td>${i + 1}</td>
                <td data-worker-label="${i}">Worker ${i + 1}</td>
                <td><input type="password" class="form-control input-ui" name="workers[${i}][password]" minlength="8"></td>
            `;
            workersPasswordTableBody.appendChild(pwTr);
        }

        applyPasswordStrategy();
    };

    const hydrateOldWorkers = () => {
        if (!Array.isArray(initialWorkers) || initialWorkers.length === 0) return;
        workersCountInput.value = String(initialWorkers.length);
        buildWorkersRows();

        const nameInputs = workersTableBody.querySelectorAll('input[name$=\"[name]\"]');
        const emailInputs = workersTableBody.querySelectorAll('input[name$=\"[email]\"]');
        const passInputs = workersPasswordTableBody.querySelectorAll('input[name$=\"[password]\"]');

        initialWorkers.forEach((worker, i) => {
            if (nameInputs[i]) nameInputs[i].value = worker.name || '';
            if (emailInputs[i]) emailInputs[i].value = worker.email || '';
            if (passInputs[i] && worker.password) passInputs[i].value = worker.password;
        });

        syncWorkerLabels();
    };

    const syncWorkerLabels = () => {
        const nameInputs = workersTableBody.querySelectorAll('input[name$="[name]"]');
        const labels = workersPasswordTableBody.querySelectorAll('td[data-worker-label]');

        labels.forEach((label, index) => {
            const name = nameInputs[index]?.value?.trim();
            label.textContent = name || `Worker ${index + 1}`;
        });
    };

    const applyPasswordStrategy = () => {
        const strategy = strategySelect.value;
        const passwordInputs = workersPasswordTableBody.querySelectorAll('input[name$="[password]"]');
        commonPasswordWrap.classList.toggle('d-none', strategy !== 'common');

        passwordInputs.forEach((input) => {
            input.required = strategy === 'manual';

            if (strategy === 'generated' && !input.value) {
                input.value = randomPassword();
            }

            if (strategy === 'common') {
                if (!input.value && commonPasswordInput.value) {
                    input.value = commonPasswordInput.value;
                }
            }
        });
    };

    form.addEventListener('input', (event) => {
        if (event.target.matches('input[name$="[name]"]')) {
            syncWorkerLabels();
        }
    });

    generateWorkersRowsBtn.addEventListener('click', buildWorkersRows);
    strategySelect.addEventListener('change', applyPasswordStrategy);
    commonPasswordInput.addEventListener('input', () => {
        if (strategySelect.value !== 'common') return;
        const inputs = workersPasswordTableBody.querySelectorAll('input[name$="[password]"]');
        inputs.forEach((input) => {
            if (!input.value || input.value.length < 8) {
                input.value = commonPasswordInput.value;
            }
        });
    });

    prevBtn.addEventListener('click', () => {
        currentStep = Math.max(1, currentStep - 1);
        updateStepState();
    });

    nextBtn.addEventListener('click', () => {
        if (currentStep === 3 && workersTableBody.children.length === 0) {
            buildWorkersRows();
        }
        currentStep = Math.min(steps.length, currentStep + 1);
        updateStepState();
    });

    buildWorkersRows();
    hydrateOldWorkers();
    updateStepState();
})();
</script>
@endsection

@extends('layouts.app')

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
                            <div class="row g-3">
                                @foreach($plans as $key => $plan)
                                    <div class="col-md-4">
                                        <label class="card card-ui h-100 p-3 border">
                                            <div class="d-flex align-items-start gap-2">
                                                <input class="form-check-input mt-1" type="radio" name="plan" value="{{ $key }}" @checked(old('plan', 'starter') === $key) required>
                                                <div>
                                                    <h3 class="h6 fw-bold mb-1">{{ $plan['label'] }}</h3>
                                                    <p class="text-muted mb-2">${{ number_format((float) $plan['price'], 0) }} MXN / mes</p>
                                                    <ul class="small text-muted mb-0">
                                                        @foreach($plan['features'] as $feature)
                                                            <li>{{ $feature }}</li>
                                                        @endforeach
                                                        @if($plan['ai_enabled'])
                                                            <li class="text-success">IA de diagnóstico ARIS incluida</li>
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
@endsection

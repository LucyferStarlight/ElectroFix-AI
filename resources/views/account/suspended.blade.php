@extends('layouts.app')

@section('title', 'Cuenta suspendida | ElectroFix-AI')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card card-ui">
                <div class="card-body p-4 p-lg-5">
                    <h1 class="h4 fw-bold mb-2">Tu cuenta requiere atención</h1>
                    <p class="text-muted mb-3">
                        @if($company?->status === 'pending_payment')
                            Tu suscripción está pendiente de pago. Completa el checkout para activar el servicio.
                        @else
                            Tu cuenta está suspendida por falta de pago.
                        @endif
                    </p>

                    @if($company?->pending_expires_at)
                        <p class="text-muted">Fecha límite: {{ $company->pending_expires_at->format('Y-m-d') }}</p>
                    @endif

                    <div class="mt-4 d-flex flex-wrap gap-2">
                        <form method="post" action="{{ route('onboarding.retry') }}">
                            @csrf
                            <button type="submit" class="btn btn-ui btn-primary-ui">Reintentar pago</button>
                        </form>
                        <a href="mailto:{{ $supportEmail }}" class="btn btn-ui btn-outline-ui">Contactar soporte</a>
                        @if(!empty($supportWhatsapp))
                            <a href="{{ $supportWhatsapp }}" class="btn btn-ui btn-outline-ui">WhatsApp</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

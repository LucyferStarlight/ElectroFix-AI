@extends('layouts.app')

@section('title', 'Bienvenida | ElectroFix-AI')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card card-ui">
                <div class="card-body p-4 p-lg-5 text-center">
                    @if($company && $status === 'active')
                        <h1 class="h4 fw-bold mb-2">¡Bienvenido, {{ $company->name }}!</h1>
                        <p class="text-muted mb-3">Tu plan <strong>{{ strtoupper($plan) }}</strong> ya está activo.</p>
                        <p class="text-muted">Recibirás un correo de confirmación en breve.</p>
                        <a href="{{ route('dashboard') }}" class="btn btn-ui btn-primary-ui mt-3">Ir al dashboard</a>
                    @else
                        <h1 class="h4 fw-bold mb-2">Procesando tu pago...</h1>
                        <p class="text-muted mb-3">Estamos confirmando tu suscripción. En cuanto finalice, podrás acceder al sistema.</p>
                        <p class="text-muted">Si ya realizaste el pago, revisa tu correo.</p>
                        <a href="{{ route('login') }}" class="btn btn-ui btn-outline-ui mt-3">Ir al login</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

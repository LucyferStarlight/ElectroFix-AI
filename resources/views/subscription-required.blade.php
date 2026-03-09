@extends('layouts.app')

@section('title', 'Redirigiendo a suscripción | ElectroFix-AI')

@section('content')
@php
    $target = auth()->check() && auth()->user()->role === 'admin'
        ? route('admin.subscription.edit')
        : route('login');
@endphp

<div class="container-fluid p-0">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card card-ui shadow-soft">
                <div class="card-body p-4 p-lg-5 text-center">
                    <span class="badge badge-ui badge-ui-warning mb-3">Suscripción requerida</span>
                    <h1 class="h4 fw-bold mb-3">Te estamos enviando al flujo de selección de plan</h1>
                    <p class="text-muted mb-4">Si la redirección no ocurre automáticamente, usa el botón para continuar.</p>
                    <a href="{{ $target }}" class="btn btn-ui btn-primary-ui">Ir a suscripción</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    window.setTimeout(function () {
        window.location.href = @json($target);
    }, 800);
</script>
@endsection

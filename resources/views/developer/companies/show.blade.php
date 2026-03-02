@extends('layouts.app')

@section('title', 'Detalle Empresa | ElectroFix-AI')

@section('content')
<div class="container-fluid p-0">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 fw-bold mb-0">{{ $company->name }}</h1>
        <a class="btn btn-ui btn-outline-ui" href="{{ route('developer.companies.index') }}">Volver</a>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card card-ui h-100"><div class="card-body">
                <h2 class="h6 fw-bold">Owner & Contacto</h2>
                <p class="mb-1"><strong>Dueño:</strong> {{ $company->owner_name }}</p>
                <p class="mb-1"><strong>Email:</strong> {{ $company->owner_email }}</p>
                <p class="mb-1"><strong>Teléfono:</strong> {{ $company->owner_phone }}</p>
                <p class="mb-1"><strong>Billing email:</strong> {{ $company->billing_email }}</p>
                <p class="mb-0"><strong>Billing phone:</strong> {{ $company->billing_phone }}</p>
            </div></div>
        </div>
        <div class="col-lg-6">
            <div class="card card-ui h-100"><div class="card-body">
                <h2 class="h6 fw-bold">Suscripción</h2>
                <p class="mb-1"><strong>Plan:</strong> <span class="text-uppercase">{{ $company->subscription?->plan ?? 'N/A' }}</span></p>
                <p class="mb-1"><strong>Estado:</strong> <span class="text-uppercase">{{ $company->subscription?->status ?? 'N/A' }}</span></p>
                <p class="mb-1"><strong>Inicio:</strong> {{ optional($company->subscription?->starts_at)->toDateString() }}</p>
                <p class="mb-1"><strong>Fin:</strong> {{ optional($company->subscription?->ends_at)->toDateString() }}</p>
                <p class="mb-0"><strong>Ciclo:</strong> {{ $company->subscription?->billing_cycle }}</p>
            </div></div>
        </div>
        <div class="col-12">
            <div class="card card-ui"><div class="card-body">
                <h2 class="h6 fw-bold">Usuarios de la empresa</h2>
                <ul class="mb-0">
                    @foreach($company->users as $user)
                        <li>{{ $user->name }} ({{ $user->role }}) - {{ $user->email }}</li>
                    @endforeach
                </ul>
            </div></div>
        </div>
    </div>
</div>
@endsection

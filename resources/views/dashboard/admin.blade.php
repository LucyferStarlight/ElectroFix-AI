@extends('layouts.app')

@section('title', 'Dashboard Admin | ElectroFix-AI')

@section('content')
<div class="container-fluid p-0">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Dashboard Administrador</h1>
            <p class="text-muted mb-0">Gestión de empresa, equipo y suscripción.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.workers.index') }}" class="btn btn-ui btn-primary-ui">Gestionar trabajadores</a>
            <a href="{{ route('admin.subscription.edit') }}" class="btn btn-ui btn-outline-ui">Suscripción</a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-4">
            <div class="card card-ui h-100"><div class="card-body"><p class="text-muted mb-1">Empresa</p><h2 class="h5 mb-0">{{ $company?->name }}</h2></div></div>
        </div>
        <div class="col-md-4">
            <div class="card card-ui h-100"><div class="card-body"><p class="text-muted mb-1">Workers registrados</p><h2 class="h3 mb-0">{{ $workersCount }}</h2></div></div>
        </div>
        <div class="col-md-4">
            <div class="card card-ui h-100"><div class="card-body"><p class="text-muted mb-1">Plan actual</p><h2 class="h5 mb-0 text-uppercase">{{ $subscription?->plan ?? 'N/A' }}</h2><small class="text-muted">Estado: {{ $subscription?->status ?? 'N/A' }}</small></div></div>
        </div>
    </div>
</div>
@endsection

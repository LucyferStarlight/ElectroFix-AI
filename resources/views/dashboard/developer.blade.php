@extends('layouts.app')

@section('title', 'Dashboard Developer | ElectroFix-AI')

@section('content')
<div class="container-fluid p-0">
    <div class="mb-4">
        <h1 class="h3 fw-bold mb-1">Dashboard Developer</h1>
        <p class="text-muted mb-0">Vista global multi-tenant para monitoreo y pruebas.</p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="card card-ui h-100"><div class="card-body"><p class="text-muted mb-1">Empresas</p><h2 class="h3 mb-0">{{ $companiesCount }}</h2></div></div></div>
        <div class="col-md-4"><div class="card card-ui h-100"><div class="card-body"><p class="text-muted mb-1">Suscripciones activas</p><h2 class="h3 mb-0">{{ $activeSubscriptions }}</h2></div></div></div>
        <div class="col-md-4"><div class="card card-ui h-100"><div class="card-body"><p class="text-muted mb-1">Usuarios totales</p><h2 class="h3 mb-0">{{ $usersCount }}</h2></div></div></div>
    </div>

    <div class="d-flex gap-2 flex-wrap">
        <a class="btn btn-ui btn-primary-ui" href="{{ route('developer.companies.index') }}">Ver empresas</a>
        <a class="btn btn-ui btn-outline-ui" href="{{ route('developer.subscriptions') }}">Ver suscripciones</a>
        <a class="btn btn-ui btn-outline-ui" href="{{ route('developer.test-company') }}">Empresa de pruebas</a>
    </div>
</div>
@endsection

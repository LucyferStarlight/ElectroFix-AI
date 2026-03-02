@extends('layouts.app')

@section('title', 'Dashboard Worker | ElectroFix-AI')

@section('content')
<div class="container-fluid p-0">
    <div class="mb-4">
        <h1 class="h3 fw-bold mb-1">Dashboard Worker</h1>
        <p class="text-muted mb-0">Módulos principales operativos para {{ $company?->name ?? 'tu empresa' }}.</p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card card-ui h-100"><div class="card-body"><p class="text-muted mb-1">Órdenes</p><h2 class="h3 mb-0">{{ $stats['orders'] }}</h2></div></div>
        </div>
        <div class="col-md-4">
            <div class="card card-ui h-100"><div class="card-body"><p class="text-muted mb-1">Clientes</p><h2 class="h3 mb-0">{{ $stats['customers'] }}</h2></div></div>
        </div>
        <div class="col-md-4">
            <div class="card card-ui h-100"><div class="card-body"><p class="text-muted mb-1">Equipos</p><h2 class="h3 mb-0">{{ $stats['equipments'] }}</h2></div></div>
        </div>
    </div>

    <div class="card card-ui">
        <div class="card-body">
            <h2 class="h5 fw-bold">Módulos principales</h2>
            <div class="d-flex gap-2 flex-wrap mt-3 mb-3">
                <a class="btn btn-ui btn-outline-ui" href="{{ route('worker.orders') }}">Órdenes</a>
                <a class="btn btn-ui btn-outline-ui" href="{{ route('worker.customers') }}">Clientes</a>
                <a class="btn btn-ui btn-outline-ui" href="{{ route('worker.equipments') }}">Equipos</a>
            </div>

            <h2 class="h6 fw-bold">Accesos delegados</h2>
            <div class="d-flex gap-2 flex-wrap mt-2">
                @if(auth()->user()->can_access_inventory || auth()->user()->role !== 'worker')
                    <a class="btn btn-ui btn-outline-ui" href="{{ route('worker.inventory') }}">Inventario</a>
                @endif
                @if(auth()->user()->can_access_billing || auth()->user()->role !== 'worker')
                    <a class="btn btn-ui btn-outline-ui" href="{{ route('worker.billing') }}">Facturación</a>
                @endif
                @if(!auth()->user()->can_access_inventory && !auth()->user()->can_access_billing && auth()->user()->role === 'worker')
                    <span class="text-muted">Sin módulos especiales asignados por administración.</span>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

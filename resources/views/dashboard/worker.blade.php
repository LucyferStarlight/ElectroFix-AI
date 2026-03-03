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
            <div class="card card-ui h-100"><div class="card-body"><p class="text-muted mb-1">Órdenes delegadas</p><h2 class="h3 mb-0">{{ $stats['orders'] }}</h2></div></div>
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

    <div class="card card-ui mt-4">
        <div class="card-body">
            <h2 class="h5 fw-bold mb-3">Órdenes delegadas a ti</h2>
            <div class="table-responsive">
                <table class="table table-ui align-middle mb-0">
                    <thead>
                        <tr>
                            <th>#Orden</th>
                            <th>Cliente</th>
                            <th>Equipo</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($delegatedOrders as $order)
                            <tr>
                                <td>#{{ str_pad((string) $order->id, 6, '0', STR_PAD_LEFT) }}</td>
                                <td>{{ $order->customer->name }}</td>
                                <td>{{ $order->equipment->brand }} {{ $order->equipment->model }}</td>
                                <td class="text-uppercase">{{ $order->status }}</td>
                                <td>{{ $order->created_at->format('Y-m-d H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No tienes órdenes delegadas actualmente.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

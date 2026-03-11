@extends('layouts.app')

@section('title', 'Dashboard Admin | ElectroFix-AI')

@section('content')
<div class="container-fluid p-0">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Dashboard Administrador</h1>
            <p class="text-muted mb-0">Gestión de empresa, equipo, suscripción y métricas operativas.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.workers.index') }}" class="btn btn-ui btn-primary-ui">Gestionar trabajadores</a>
            <a href="{{ route('admin.subscription.edit') }}" class="btn btn-ui btn-outline-ui">Suscripción</a>
        </div>
    </div>

    <div class="card card-ui mb-3">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-end gap-3">
            <div>
                <p class="text-muted mb-1">Rango de métricas</p>
                <p class="mb-0 fw-semibold">{{ $range['from'] }} a {{ $range['to'] }}</p>
            </div>
            <form method="get" class="d-flex flex-wrap align-items-end gap-2">
                <div>
                    <label class="form-label mb-1">Desde</label>
                    <input type="date" class="form-control input-ui" name="from" value="{{ $range['from'] }}">
                </div>
                <div>
                    <label class="form-label mb-1">Hasta</label>
                    <input type="date" class="form-control input-ui" name="to" value="{{ $range['to'] }}">
                </div>
                <button class="btn btn-ui btn-outline-ui" type="submit">Actualizar</button>
            </form>
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

    <div class="row g-3 mt-1">
        <div class="col-md-3">
            <div class="card card-ui h-100"><div class="card-body"><p class="text-muted mb-1">Órdenes (periodo)</p><h2 class="h3 mb-0">{{ $metrics['orders']['total'] }}</h2></div></div>
        </div>
        <div class="col-md-3">
            <div class="card card-ui h-100"><div class="card-body"><p class="text-muted mb-1">Órdenes hoy</p><h2 class="h3 mb-0">{{ $metrics['orders']['today'] }}</h2></div></div>
        </div>
        <div class="col-md-3">
            <div class="card card-ui h-100"><div class="card-body"><p class="text-muted mb-1">Facturado neto</p><h2 class="h5 mb-0">${{ number_format((float) $metrics['revenue']['net'], 2) }}</h2></div></div>
        </div>
        <div class="col-md-3">
            <div class="card card-ui h-100"><div class="card-body"><p class="text-muted mb-1">Facturado total</p><h2 class="h5 mb-0">${{ number_format((float) $metrics['revenue']['gross'], 2) }}</h2></div></div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-lg-4">
            <div class="card card-ui h-100">
                <div class="card-body">
                    <h2 class="h6 fw-bold">Órdenes por estado</h2>
                    <div class="table-responsive mt-3">
                        <table class="table table-ui align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Estado</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($metrics['orders']['by_status'] as $status => $total)
                                    <tr>
                                        <td class="text-uppercase">{{ $status }}</td>
                                        <td class="text-end">{{ $total }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-center text-muted py-3">Sin datos en el periodo seleccionado.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card card-ui h-100">
                <div class="card-body">
                    <h2 class="h6 fw-bold">Diagnóstico IA</h2>
                    <div class="mt-3 d-grid gap-2">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Exitosos</span>
                            <strong>{{ $metrics['ai']['diagnostics_success'] }}</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Bloqueados</span>
                            <strong>{{ $metrics['ai']['diagnostics_blocked'] }}</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Tasa aceptación</span>
                            <strong>{{ number_format((float) $metrics['ai']['acceptance_rate'], 2) }}%</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card card-ui h-100">
                <div class="card-body">
                    <h2 class="h6 fw-bold">Técnicos</h2>
                    <div class="mt-3 d-grid gap-2">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Activos</span>
                            <strong>{{ $metrics['technicians']['active'] }}</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Disponibles</span>
                            <strong>{{ $metrics['technicians']['available'] }}</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Asignados</span>
                            <strong>{{ $metrics['technicians']['assigned'] }}</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Inactivos</span>
                            <strong>{{ $metrics['technicians']['inactive'] }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-ui mt-3">
        <div class="card-body">
            <h2 class="h6 fw-bold mb-3">Equipos con mayor reincidencia</h2>
            <div class="table-responsive">
                <table class="table table-ui align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Equipo</th>
                            <th>Serie</th>
                            <th class="text-end">Órdenes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($metrics['equipment_reincidence'] as $row)
                            <tr>
                                <td>{{ $row['equipment'] ?: 'Equipo sin nombre' }}</td>
                                <td>{{ $row['serial_number'] ?: 'N/A' }}</td>
                                <td class="text-end">{{ $row['total_orders'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">Sin reincidencias registradas en el periodo.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

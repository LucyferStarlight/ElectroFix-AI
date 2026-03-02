@extends('layouts.app')

@section('title', 'Equipos | ElectroFix-AI')

@section('content')
<div class="container-fluid p-0">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Inventario de Equipos</h1>
            <p class="text-muted mb-0">Electrodomésticos registrados y vinculados a clientes.</p>
        </div>
        <button class="btn btn-ui btn-primary-ui" data-bs-toggle="modal" data-bs-target="#equipmentModal">Registrar Equipo</button>
    </div>

    <div class="card card-ui mb-4">
        <div class="card-body d-flex gap-3 flex-wrap align-items-center">
            <form method="get" class="d-flex gap-2 flex-grow-1">
                <input class="form-control input-ui" type="text" name="search" value="{{ $search }}" placeholder="Buscar por marca, modelo, tipo o dueño...">
                <button class="btn btn-ui btn-outline-ui" type="submit">Buscar</button>
            </form>
            <span class="badge badge-ui badge-ui-info">{{ $equipments->total() }} equipos</span>
        </div>
    </div>

    <div class="row g-3">
        @forelse($equipments as $equipment)
            <div class="col-md-6 col-xl-4">
                <div class="card card-ui h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h2 class="h5 fw-bold mb-0">{{ $equipment->brand }}</h2>
                            <span class="badge badge-ui badge-ui-info text-uppercase">{{ $equipment->type }}</span>
                        </div>
                        <p class="text-muted mb-2">{{ $equipment->model ?: 'Modelo no definido' }}</p>
                        <p class="mb-1"><strong>Propietario:</strong> {{ $equipment->customer->name }}</p>
                        <p class="mb-0"><strong>Nro Serie:</strong> {{ $equipment->serial_number ?: 'N/A' }}</p>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12"><div class="card card-ui"><div class="card-body text-center text-muted py-5">No se encontraron equipos.</div></div></div>
        @endforelse
    </div>

    <div class="mt-3">{{ $equipments->links() }}</div>
</div>

<div class="modal fade" id="equipmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content card-ui">
            <form method="post" action="{{ route('worker.equipments.store') }}">
                @csrf
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Registrar Nuevo Equipo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Cliente (Dueño) *</label>
                            <select class="form-select input-ui" name="customer_id" required>
                                <option value="">Seleccionar cliente...</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}">{{ $customer->name }} ({{ $customer->email }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6"><label class="form-label">Tipo *</label><input class="form-control input-ui" name="type" placeholder="Lavadora" required></div>
                        <div class="col-md-6"><label class="form-label">Marca *</label><input class="form-control input-ui" name="brand" placeholder="Samsung" required></div>
                        <div class="col-md-6"><label class="form-label">Modelo</label><input class="form-control input-ui" name="model"></div>
                        <div class="col-md-6"><label class="form-label">Número de Serie</label><input class="form-control input-ui" name="serial_number"></div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button class="btn btn-ui btn-outline-ui" type="button" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-ui btn-primary-ui" type="submit">Registrar Equipo</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

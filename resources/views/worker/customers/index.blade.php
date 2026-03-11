@extends('layouts.app')

@section('title', 'Clientes | ElectroFix-AI')

@section('content')
<div class="container-fluid p-0">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Gestión de Clientes</h1>
            <p class="text-muted mb-0">Administra la base de datos de clientes y sus contactos.</p>
        </div>
        <button class="btn btn-ui btn-primary-ui" data-bs-toggle="modal" data-bs-target="#customerModal">Nuevo Cliente</button>
    </div>

    <div class="card card-ui mb-4">
        <div class="card-body d-flex gap-3 flex-wrap align-items-center">
            <form method="get" class="d-flex gap-2 flex-grow-1">
                <input class="form-control input-ui" type="text" name="search" value="{{ $search }}" placeholder="Buscar por nombre, email o teléfono...">
                <button class="btn btn-ui btn-outline-ui" type="submit">Buscar</button>
            </form>
            <span class="badge badge-ui badge-ui-info">{{ $customers->total() }} clientes</span>
        </div>
    </div>

    <div class="card card-ui">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-ui align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="px-4">Cliente</th>
                            <th>Contacto</th>
                            <th>Ubicación</th>
                            <th>Registro</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customers as $customer)
                            <tr>
                                <td class="px-4 fw-semibold">{{ $customer->name }}</td>
                                <td>
                                    <div>{{ $customer->email }}</div>
                                    <small class="text-muted">{{ $customer->phone ?: 'Sin teléfono' }}</small>
                                </td>
                                <td>{{ $customer->address ?: 'Sin dirección' }}</td>
                                <td>{{ $customer->created_at->format('Y-m-d') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-5">No se encontraron clientes.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3">{{ $customers->links() }}</div>
</div>

<div class="modal fade" id="customerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content card-ui">
            <form method="post" action="{{ route('worker.customers.store') }}">
                @csrf
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Registrar Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Nombre *</label><input class="form-control input-ui" name="name" required></div>
                        <div class="col-md-6"><label class="form-label">Correo *</label><input type="email" class="form-control input-ui" name="email" required></div>
                        <div class="col-md-6"><label class="form-label">Teléfono</label><input class="form-control input-ui" name="phone"></div>
                        <div class="col-md-6"><label class="form-label">Dirección</label><input class="form-control input-ui" name="address"></div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button class="btn btn-ui btn-outline-ui" type="button" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-ui btn-primary-ui" type="submit">Guardar Cliente</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

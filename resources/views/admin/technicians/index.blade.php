@extends('layouts.app')

@section('title', 'Técnicos | ElectroFix-AI')

@section('content')
<div class="container-fluid p-0">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Técnicos</h1>
            <p class="text-muted mb-0">Gestión operativa de técnicos y su disponibilidad de trabajo.</p>
        </div>
    </div>

    @if(session('temporary_credentials'))
        <div class="alert alert-warning">
            <strong>Técnico creado correctamente.</strong><br>
            Email: <code>{{ session('temporary_credentials.email') }}</code><br>
            Contraseña temporal: <code>{{ session('temporary_credentials.password') }}</code><br>
            <small>Comparte esta contraseña de forma segura. Se muestra una sola vez.</small>
        </div>
    @endif

    <div class="card card-ui mb-4">
        <div class="card-body">
            <form method="post" action="{{ route('admin.technicians.store') }}" class="row g-3">
                @csrf
                <div class="col-md-2">
                    <label class="form-label">Código</label>
                    <input class="form-control input-ui" name="employee_code" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Nombre técnico</label>
                    <input class="form-control input-ui" name="display_name" required>
                    @error('display_name')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Email *</label>
                    <input type="email" class="form-control input-ui" name="email" value="{{ old('email') }}" required>
                    @error('email')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-2">
                    <label class="form-label">Estado</label>
                    <select class="form-select input-ui" name="status" required>
                        @foreach($statuses as $status)
                            <option value="{{ $status }}">{{ strtoupper($status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Máx. órdenes</label>
                    <input class="form-control input-ui" type="number" min="1" max="100" value="5" name="max_concurrent_orders" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Costo/hora</label>
                    <input class="form-control input-ui" type="number" step="0.01" min="0" name="hourly_cost" value="0">
                </div>
                <div class="col-md-9">
                    <label class="form-label">Especialidades</label>
                    <input class="form-control input-ui" name="specialties[]" placeholder="Ejemplo: Refrigeración (puedes dejar vacío)">
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" checked name="is_assignable" id="is_assignable">
                        <label class="form-check-label" for="is_assignable">Disponible para asignaciones</label>
                    </div>
                </div>
                <div class="col-12">
                    <div class="technician-permissions-panel bg-white border rounded p-3">
                        <p class="fw-semibold mb-2">Permisos del técnico</p>
                        @include('admin.technicians.partials.permissions', [
                            'inventoryId' => 'can_access_inventory',
                            'billingId' => 'can_access_billing',
                            'canAccessInventory' => old('can_access_inventory'),
                            'canAccessBilling' => old('can_access_billing'),
                        ])
                    </div>
                </div>
                <div class="col-12">
                    <button class="btn btn-ui btn-primary-ui" type="submit">Crear técnico</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card card-ui">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-ui align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="px-3">Código</th>
                            <th>Nombre</th>
                            <th>Usuario</th>
                            <th>Estado</th>
                            <th>Asignable</th>
                            <th>Max Órdenes</th>
                            <th>Costo/Hora</th>
                            <th>Permisos</th>
                            <th class="pe-3 text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($technicians as $technician)
                            <tr>
                                <td class="px-3">{{ $technician->employee_code }}</td>
                                <td>{{ $technician->display_name }}</td>
                                <td>{{ $technician->user?->name ?? 'Sin usuario' }}</td>
                                <td class="text-uppercase">{{ $technician->status }}</td>
                                <td>{{ $technician->is_assignable ? 'Sí' : 'No' }}</td>
                                <td>{{ $technician->max_concurrent_orders }}</td>
                                <td>${{ number_format((float) $technician->hourly_cost, 2) }}</td>
                                <td>
                                    <span class="badge text-bg-secondary">Fac: {{ $technician->user?->can_access_billing ? 'Sí' : 'No' }}</span>
                                    <span class="badge text-bg-secondary">Inv: {{ $technician->user?->can_access_inventory ? 'Sí' : 'No' }}</span>
                                </td>
                                <td class="pe-3 text-end">
                                    <button class="btn btn-ui btn-outline-ui btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#edit-technician-{{ $technician->id }}">Editar</button>
                                    <form method="post" action="{{ route('admin.technicians.deactivate', $technician) }}" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn btn-ui btn-outline-ui btn-sm" type="submit">Desactivar</button>
                                    </form>
                                </td>
                            </tr>
                            <tr class="collapse" id="edit-technician-{{ $technician->id }}">
                                <td colspan="9" class="bg-body-tertiary">
                                    <form method="post" action="{{ route('admin.technicians.update', $technician) }}" class="row g-3 p-2">
                                        @csrf
                                        @method('PUT')
                                        <div class="col-md-4">
                                            <label class="form-label">Nombre técnico</label>
                                            <input class="form-control input-ui" name="display_name" value="{{ $technician->display_name }}" required>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Estado</label>
                                            <select class="form-select input-ui" name="status" required>
                                                @foreach($statuses as $status)
                                                    <option value="{{ $status }}" @selected($technician->status === $status)>{{ strtoupper($status) }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Máx. órdenes</label>
                                            <input class="form-control input-ui" type="number" min="1" max="100" name="max_concurrent_orders" value="{{ $technician->max_concurrent_orders }}" required>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Costo/hora</label>
                                            <input class="form-control input-ui" type="number" min="0" step="0.01" name="hourly_cost" value="{{ $technician->hourly_cost }}">
                                        </div>
                                        <div class="col-md-2 d-flex align-items-end">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="is_assignable" value="1" id="is-assignable-{{ $technician->id }}" @checked($technician->is_assignable)>
                                                <label class="form-check-label" for="is-assignable-{{ $technician->id }}">Asignable</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Especialidades</label>
                                            <input class="form-control input-ui" name="specialties[]" value="{{ implode(', ', (array) $technician->specialties) }}">
                                        </div>
                                        <div class="col-md-6">
                                            <div class="technician-permissions-panel bg-white border rounded p-3">
                                            <label class="form-label d-block">Permisos del técnico</label>
                                            @include('admin.technicians.partials.permissions', [
                                                'inventoryId' => 'inventory-' . $technician->id,
                                                'billingId' => 'billing-' . $technician->id,
                                                'canAccessInventory' => $technician->user?->can_access_inventory,
                                                'canAccessBilling' => $technician->user?->can_access_billing,
                                            ])
                                            </div>
                                        </div>
                                        <div class="col-12 text-end">
                                            <button class="btn btn-ui btn-primary-ui btn-sm" type="submit">Guardar cambios</button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center text-muted py-5">Sin técnicos registrados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3">{{ $technicians->links() }}</div>
</div>
@endsection

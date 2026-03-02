@extends('layouts.app')

@section('title', 'Trabajadores | ElectroFix-AI')

@section('content')
<div class="container-fluid p-0">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h1 class="h4 fw-bold mb-1">Gestión de trabajadores</h1>
            <p class="text-muted mb-0">Administra personal y permisos especiales (Facturación e Inventario).</p>
        </div>
    </div>

    <div class="card card-ui mb-4">
        <div class="card-body">
            <h2 class="h6 fw-bold mb-3">Nuevo trabajador</h2>
            <form class="row g-3" method="post" action="{{ route('admin.workers.store') }}">
                @csrf
                <div class="col-md-4"><input class="form-control input-ui" name="name" placeholder="Nombre" required></div>
                <div class="col-md-4"><input class="form-control input-ui" type="email" name="email" placeholder="Correo" required></div>
                <div class="col-md-4"><input class="form-control input-ui" type="password" name="password" placeholder="Contraseña" required></div>
                <div class="col-md-6 form-check ms-2"><input class="form-check-input" type="checkbox" value="1" id="newBilling" name="can_access_billing"><label class="form-check-label" for="newBilling">Permitir Facturación</label></div>
                <div class="col-md-6 form-check ms-2"><input class="form-check-input" type="checkbox" value="1" id="newInventory" name="can_access_inventory"><label class="form-check-label" for="newInventory">Permitir Inventario</label></div>
                <div class="col-12"><button class="btn btn-ui btn-primary-ui" type="submit">Crear trabajador</button></div>
            </form>
        </div>
    </div>

    <div class="card card-ui">
        <div class="card-body">
            <h2 class="h6 fw-bold mb-3">Trabajadores registrados</h2>
            <div class="table-responsive">
                <table class="table table-ui align-middle">
                    <thead>
                        <tr>
                            <th>Worker y permisos especiales</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($workers as $worker)
                            <tr>
                                <td colspan="3">
                                    <form class="row g-2 align-items-center" method="post" action="{{ route('admin.workers.update', $worker) }}">
                                        @csrf
                                        @method('PUT')
                                        <div class="col-md-3"><input class="form-control input-ui form-control-sm" name="name" value="{{ $worker->name }}" required></div>
                                        <div class="col-md-3"><input class="form-control input-ui form-control-sm" name="email" type="email" value="{{ $worker->email }}" required></div>
                                        <div class="col-md-2 form-check">
                                            <input class="form-check-input" type="checkbox" id="billing{{ $worker->id }}" name="can_access_billing" value="1" {{ $worker->can_access_billing ? 'checked' : '' }}>
                                            <label class="form-check-label" for="billing{{ $worker->id }}">Facturación</label>
                                        </div>
                                        <div class="col-md-2 form-check">
                                            <input class="form-check-input" type="checkbox" id="inventory{{ $worker->id }}" name="can_access_inventory" value="1" {{ $worker->can_access_inventory ? 'checked' : '' }}>
                                            <label class="form-check-label" for="inventory{{ $worker->id }}">Inventario</label>
                                        </div>
                                        <div class="col-md-2"><button class="btn btn-ui btn-outline-ui btn-sm w-100" type="submit">Guardar</button></div>
                                    </form>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        @if($worker->is_active)
                                            <span class="badge badge-ui badge-ui-success align-self-center">Activo</span>
                                        @else
                                            <span class="badge badge-ui badge-ui-warning align-self-center">Inactivo</span>
                                        @endif
                                        <form method="post" action="{{ route('admin.workers.deactivate', $worker) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="btn btn-ui btn-outline-ui btn-sm" type="submit">{{ $worker->is_active ? 'Desactivar' : 'Reactivar' }}</button>
                                        </form>
                                        <form method="post" action="{{ route('admin.workers.destroy', $worker) }}" onsubmit="return confirm('¿Eliminar definitivamente este trabajador?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-ui btn-sm btn-danger" type="submit">Eliminar</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="text-muted">Sin trabajadores registrados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

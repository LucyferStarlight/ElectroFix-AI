@extends('layouts.app')

@section('title', 'Empresa | ElectroFix-AI')

@section('content')
<div class="container-fluid p-0">
    <div class="card card-ui">
        <div class="card-body">
            <h1 class="h4 fw-bold mb-3">Datos de empresa</h1>
            <form method="post" action="{{ route('admin.company.update') }}" class="row g-3">
                @csrf
                @method('PUT')
                <div class="col-md-6"><label class="form-label">Nombre</label><input class="form-control input-ui" name="name" value="{{ old('name', $company->name) }}" required></div>
                <div class="col-md-6"><label class="form-label">Dueño</label><input class="form-control input-ui" name="owner_name" value="{{ old('owner_name', $company->owner_name) }}" required></div>
                <div class="col-md-6"><label class="form-label">Email dueño</label><input type="email" class="form-control input-ui" name="owner_email" value="{{ old('owner_email', $company->owner_email) }}" required></div>
                <div class="col-md-6"><label class="form-label">Teléfono dueño</label><input class="form-control input-ui" name="owner_phone" value="{{ old('owner_phone', $company->owner_phone) }}" required></div>
                <div class="col-md-4"><label class="form-label">RFC/NIT</label><input class="form-control input-ui" name="tax_id" value="{{ old('tax_id', $company->tax_id) }}"></div>
                <div class="col-md-4"><label class="form-label">Email billing</label><input type="email" class="form-control input-ui" name="billing_email" value="{{ old('billing_email', $company->billing_email) }}"></div>
                <div class="col-md-4"><label class="form-label">Tel. billing</label><input class="form-control input-ui" name="billing_phone" value="{{ old('billing_phone', $company->billing_phone) }}"></div>
                <div class="col-md-6"><label class="form-label">Dirección</label><input class="form-control input-ui" name="address_line" value="{{ old('address_line', $company->address_line) }}"></div>
                <div class="col-md-3"><label class="form-label">Ciudad</label><input class="form-control input-ui" name="city" value="{{ old('city', $company->city) }}"></div>
                <div class="col-md-3"><label class="form-label">Estado</label><input class="form-control input-ui" name="state" value="{{ old('state', $company->state) }}"></div>
                <div class="col-md-3"><label class="form-label">País</label><input class="form-control input-ui" name="country" value="{{ old('country', $company->country) }}" required></div>
                <div class="col-md-3"><label class="form-label">CP</label><input class="form-control input-ui" name="postal_code" value="{{ old('postal_code', $company->postal_code) }}"></div>
                <div class="col-md-3"><label class="form-label">Moneda</label><input class="form-control input-ui" name="currency" value="{{ old('currency', $company->currency) }}" required></div>
                <div class="col-md-9"><label class="form-label">Notas</label><input class="form-control input-ui" name="notes" value="{{ old('notes', $company->notes) }}"></div>
                <div class="col-12"><button class="btn btn-ui btn-primary-ui" type="submit">Guardar cambios</button></div>
            </form>
        </div>
    </div>
</div>
@endsection

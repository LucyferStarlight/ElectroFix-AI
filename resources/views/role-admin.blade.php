@extends('layouts.app')

@section('title', 'Módulo Administración | ElectroFix-AI')

@section('content')
<div class="container-fluid p-0">
    <div class="card card-ui"><div class="card-body">
        <h1 class="h4 fw-bold mb-3">Accesos de administración</h1>
        <div class="d-flex gap-2 flex-wrap">
            <a class="btn btn-ui btn-primary-ui" href="{{ route('dashboard.admin') }}">Dashboard Admin</a>
            <a class="btn btn-ui btn-outline-ui" href="{{ route('admin.workers.index') }}">Workers</a>
            <a class="btn btn-ui btn-outline-ui" href="{{ route('admin.company.edit') }}">Empresa</a>
            <a class="btn btn-ui btn-outline-ui" href="{{ route('admin.subscription.edit') }}">Suscripción</a>
        </div>
    </div></div>
</div>
@endsection

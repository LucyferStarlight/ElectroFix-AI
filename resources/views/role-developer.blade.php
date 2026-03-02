@extends('layouts.app')

@section('title', 'Módulo Desarrollador | ElectroFix-AI')

@section('content')
<div class="container-fluid p-0">
    <div class="card card-ui"><div class="card-body">
        <h1 class="h4 fw-bold mb-3">Accesos Developer</h1>
        <div class="d-flex gap-2 flex-wrap">
            <a class="btn btn-ui btn-primary-ui" href="{{ route('dashboard.developer') }}">Dashboard Developer</a>
            <a class="btn btn-ui btn-outline-ui" href="{{ route('developer.companies.index') }}">Empresas</a>
            <a class="btn btn-ui btn-outline-ui" href="{{ route('developer.subscriptions') }}">Suscripciones</a>
            <a class="btn btn-ui btn-outline-ui" href="{{ route('developer.test-company') }}">Empresa de Pruebas</a>
        </div>
    </div></div>
</div>
@endsection
